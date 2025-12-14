<?php
/**
 * Campaign Processor
 * Handles background processing of campaigns
 *
 * @package U43
 */

namespace U43\Campaigns;

class Campaign_Processor {
    
    private $campaign_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->campaign_manager = new Campaign_Manager();
        
        // Register custom cron interval
        add_filter('cron_schedules', function($schedules) {
            $schedules['u43_campaign_interval'] = [
                'interval' => 60, // Every minute
                'display' => __('Every Minute (U43 Campaigns)', 'u43')
            ];
            return $schedules;
        });
        
        // Register WP-Cron hook
        add_action('u43_process_campaigns', [$this, 'process_campaigns']);
        
        // Schedule recurring event if not already scheduled
        if (!wp_next_scheduled('u43_process_campaigns')) {
            wp_schedule_event(time(), 'u43_campaign_interval', 'u43_process_campaigns');
        }
    }
    
    /**
     * Process running campaigns
     */
    public function process_campaigns() {
        global $wpdb;
        
        $campaigns_table = $wpdb->prefix . 'u43_campaigns';
        
        // Get all running campaigns
        $running_campaigns = $wpdb->get_results(
            "SELECT * FROM $campaigns_table 
            WHERE status = 'running' 
            AND (scheduled_at IS NULL OR scheduled_at <= NOW())
            ORDER BY created_at ASC
            LIMIT 5"
        );
        
        foreach ($running_campaigns as $campaign) {
            $this->process_campaign($campaign->id);
        }
        
        // Check for scheduled campaigns that need to start
        $scheduled_campaigns = $wpdb->get_results(
            "SELECT * FROM $campaigns_table 
            WHERE status = 'scheduled' 
            AND scheduled_at <= NOW()
            LIMIT 5"
        );
        
        foreach ($scheduled_campaigns as $campaign) {
            $this->campaign_manager->start_campaign($campaign->id);
            $this->process_campaign($campaign->id);
        }
    }
    
    /**
     * Process a single campaign
     *
     * @param int $campaign_id Campaign ID
     */
    public function process_campaign($campaign_id) {
        $campaign = $this->campaign_manager->get_campaign($campaign_id);
        
        if (!$campaign || $campaign->status !== 'running') {
            return;
        }
        
        // Process batch
        $batch_size = $campaign->batch_size ?: 100;
        $result = $this->campaign_manager->process_campaign_batch($campaign_id, $batch_size);
        
        // Log result
        error_log("U43 Campaign Processor: Campaign {$campaign_id} - Sent: {$result['sent']}, Failed: {$result['failed']}");
        
        // Update campaign stats
        $this->campaign_manager->update_campaign_stats($campaign_id);
    }
    
}

