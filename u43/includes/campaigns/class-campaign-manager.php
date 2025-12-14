<?php
/**
 * Campaign Manager
 *
 * @package U43
 */

namespace U43\Campaigns;

use U43\Integrations\WhatsApp\WhatsApp_API_Client;

class Campaign_Manager {
    
    private $wpdb;
    private $whatsapp_client;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->whatsapp_client = new WhatsApp_API_Client();
    }
    
    /**
     * Get all campaigns
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_campaigns($args = []) {
        $defaults = [
            'status' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $this->wpdb->prefix . 'u43_campaigns';
        $where = '1=1';
        
        if (!empty($args['status'])) {
            $where .= $this->wpdb->prepare(' AND status = %s', $args['status']);
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT * FROM $table WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d";
        $campaigns = $this->wpdb->get_results($this->wpdb->prepare($query, $args['per_page'], $offset));
        
        $total = $this->wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");
        
        return [
            'items' => $campaigns,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page'])
        ];
    }
    
    /**
     * Get campaign by ID
     *
     * @param int $campaign_id Campaign ID
     * @return object|null
     */
    public function get_campaign($campaign_id) {
        $table = $this->wpdb->prefix . 'u43_campaigns';
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $campaign_id));
    }
    
    /**
     * Create campaign
     *
     * @param array $data Campaign data
     * @return int|false Campaign ID or false on failure
     */
    public function create_campaign($data) {
        $table = $this->wpdb->prefix . 'u43_campaigns';
        
        $defaults = [
            'name' => '',
            'description' => '',
            'message_text' => '',
            'template_name' => '',
            'template_params' => '',
            'target_type' => 'all',
            'target_value' => '',
            'schedule_type' => 'immediate',
            'scheduled_at' => null,
            'status' => 'draft',
            'batch_size' => 100,
            'created_by' => get_current_user_id()
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Serialize arrays
        if (is_array($data['template_params'])) {
            $data['template_params'] = json_encode($data['template_params']);
        }
        if (is_array($data['target_value'])) {
            $data['target_value'] = json_encode($data['target_value']);
        }
        
        $result = $this->wpdb->insert($table, $data);
        
        if ($result) {
            $campaign_id = $this->wpdb->insert_id;
            
            // Get target contacts and create campaign-contact relationships
            $contacts = $this->get_target_contacts($data['target_type'], $data['target_value']);
            $this->add_contacts_to_campaign($campaign_id, $contacts);
            
            return $campaign_id;
        }
        
        return false;
    }
    
    /**
     * Update campaign
     *
     * @param int $campaign_id Campaign ID
     * @param array $data Campaign data
     * @return bool
     */
    public function update_campaign($campaign_id, $data) {
        $table = $this->wpdb->prefix . 'u43_campaigns';
        
        // Serialize arrays
        if (isset($data['template_params']) && is_array($data['template_params'])) {
            $data['template_params'] = json_encode($data['template_params']);
        }
        if (isset($data['target_value']) && is_array($data['target_value'])) {
            $data['target_value'] = json_encode($data['target_value']);
        }
        
        return $this->wpdb->update(
            $table,
            $data,
            ['id' => $campaign_id]
        ) !== false;
    }
    
    /**
     * Delete campaign
     *
     * @param int $campaign_id Campaign ID
     * @return bool
     */
    public function delete_campaign($campaign_id) {
        $table = $this->wpdb->prefix . 'u43_campaigns';
        return $this->wpdb->delete($table, ['id' => $campaign_id]) !== false;
    }
    
    /**
     * Get target contacts based on campaign target settings
     *
     * @param string $target_type Target type (all, tags, folder)
     * @param string|array $target_value Target value
     * @return array Contact IDs
     */
    public function get_target_contacts($target_type, $target_value) {
        $contacts_table = $this->wpdb->prefix . 'u43_campaign_contacts';
        $contact_tags_table = $this->wpdb->prefix . 'u43_campaign_contact_tags';
        
        if ($target_type === 'all') {
            return $this->wpdb->get_col("SELECT id FROM $contacts_table");
        } elseif ($target_type === 'folder') {
            $folder_id = is_array($target_value) ? $target_value[0] : $target_value;
            return $this->wpdb->get_col($this->wpdb->prepare(
                "SELECT id FROM $contacts_table WHERE folder_id = %d",
                $folder_id
            ));
        } elseif ($target_type === 'tags') {
            $tag_ids = is_array($target_value) ? $target_value : (is_string($target_value) ? json_decode($target_value, true) : []);
            if (empty($tag_ids) || !is_array($tag_ids)) {
                return [];
            }
            
            $placeholders = implode(',', array_fill(0, count($tag_ids), '%d'));
            $query = "SELECT DISTINCT contact_id FROM $contact_tags_table WHERE tag_id IN ($placeholders)";
            return $this->wpdb->get_col($this->wpdb->prepare($query, ...$tag_ids));
        }
        
        return [];
    }
    
    /**
     * Add contacts to campaign
     *
     * @param int $campaign_id Campaign ID
     * @param array $contact_ids Contact IDs
     * @return bool
     */
    public function add_contacts_to_campaign($campaign_id, $contact_ids) {
        $table = $this->wpdb->prefix . 'u43_campaign_contacts_rel';
        
        // Delete existing relationships
        $this->wpdb->delete($table, ['campaign_id' => $campaign_id]);
        
        // Update total contacts count
        $this->update_campaign($campaign_id, ['total_contacts' => count($contact_ids)]);
        
        // Insert new relationships
        foreach ($contact_ids as $contact_id) {
            $this->wpdb->insert($table, [
                'campaign_id' => $campaign_id,
                'contact_id' => $contact_id,
                'status' => 'pending'
            ]);
        }
        
        return true;
    }
    
    /**
     * Process campaign batch
     *
     * @param int $campaign_id Campaign ID
     * @param int $batch_size Batch size
     * @return array Results
     */
    public function process_campaign_batch($campaign_id, $batch_size = 100) {
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found'];
        }
        
        // Get pending contacts
        $rel_table = $this->wpdb->prefix . 'u43_campaign_contacts_rel';
        $contacts_table = $this->wpdb->prefix . 'u43_campaign_contacts';
        
        $pending = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT ccr.*, c.name, c.phone 
            FROM $rel_table ccr
            JOIN $contacts_table c ON ccr.contact_id = c.id
            WHERE ccr.campaign_id = %d AND ccr.status = 'pending'
            LIMIT %d",
            $campaign_id,
            $batch_size
        ));
        
        if (empty($pending)) {
            return ['success' => true, 'message' => 'No pending contacts'];
        }
        
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($pending as $item) {
            $result = $this->send_campaign_message($campaign, $item);
            
            if ($result['success']) {
                $results['sent']++;
                $this->update_campaign_contact_status(
                    $item->id,
                    'sent',
                    ['whatsapp_message_id' => $result['message_id'] ?? null]
                );
                $this->add_campaign_log($campaign_id, $item->contact_id, $item->id, 'success', 'Message sent successfully');
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'contact_id' => $item->contact_id,
                    'error' => $result['message']
                ];
                
                // Update retry count
                $retry_count = $item->retry_count + 1;
                $status = ($retry_count >= 3) ? 'failed' : 'pending';
                
                $this->update_campaign_contact_status(
                    $item->id,
                    $status,
                    [
                        'error_message' => $result['message'],
                        'retry_count' => $retry_count
                    ]
                );
                
                $this->add_campaign_log($campaign_id, $item->contact_id, $item->id, 'error', $result['message']);
            }
        }
        
        // Update campaign stats
        $this->update_campaign_stats($campaign_id);
        
        return $results;
    }
    
    /**
     * Send campaign message to a contact
     *
     * @param object $campaign Campaign object
     * @param object $campaign_contact Campaign contact relationship
     * @return array
     */
    private function send_campaign_message($campaign, $campaign_contact) {
        $phone = $campaign_contact->phone;
        
        // Use template if available, otherwise use plain text
        if (!empty($campaign->template_name)) {
            $template_params = json_decode($campaign->template_params, true);
            $result = $this->whatsapp_client->send_marketing_message(
                $phone,
                $campaign->template_name,
                $template_params ?: [],
                'en_US'
            );
        } else {
            // For plain text, we need to use regular message API
            // Note: Marketing messages should use templates, but we'll support plain text for flexibility
            $result = $this->whatsapp_client->send_message($phone, $campaign->message_text);
        }
        
        if ($result['success']) {
            return [
                'success' => true,
                'message_id' => $result['data']['messages'][0]['id'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Unknown error'
        ];
    }
    
    /**
     * Update campaign contact status
     *
     * @param int $campaign_contact_id Campaign contact ID
     * @param string $status Status
     * @param array $data Additional data
     * @return bool
     */
    public function update_campaign_contact_status($campaign_contact_id, $status, $data = []) {
        $table = $this->wpdb->prefix . 'u43_campaign_contacts_rel';
        
        $update_data = ['status' => $status];
        
        if ($status === 'sent') {
            $update_data['sent_at'] = current_time('mysql');
        }
        
        $update_data = array_merge($update_data, $data);
        
        return $this->wpdb->update(
            $table,
            $update_data,
            ['id' => $campaign_contact_id]
        ) !== false;
    }
    
    /**
     * Add campaign log
     *
     * @param int $campaign_id Campaign ID
     * @param int $contact_id Contact ID
     * @param int $campaign_contact_id Campaign contact ID
     * @param string $log_type Log type
     * @param string $message Log message
     * @param array $metadata Additional metadata
     * @return int|false Log ID or false
     */
    public function add_campaign_log($campaign_id, $contact_id, $campaign_contact_id, $log_type, $message, $metadata = []) {
        $table = $this->wpdb->prefix . 'u43_campaign_logs';
        
        return $this->wpdb->insert($table, [
            'campaign_id' => $campaign_id,
            'contact_id' => $contact_id,
            'campaign_contact_id' => $campaign_contact_id,
            'log_type' => $log_type,
            'message' => $message,
            'metadata' => json_encode($metadata)
        ]);
    }
    
    /**
     * Update campaign statistics
     *
     * @param int $campaign_id Campaign ID
     * @return bool
     */
    public function update_campaign_stats($campaign_id) {
        $rel_table = $this->wpdb->prefix . 'u43_campaign_contacts_rel';
        
        $stats = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM $rel_table
            WHERE campaign_id = %d",
            $campaign_id
        ));
        
        $update_data = [
            'sent_count' => (int) $stats->sent,
            'failed_count' => (int) $stats->failed
        ];
        
        // Check if campaign is completed
        $pending = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $rel_table WHERE campaign_id = %d AND status = 'pending'",
            $campaign_id
        ));
        
        if ($pending == 0) {
            $update_data['status'] = 'completed';
            $update_data['completed_at'] = current_time('mysql');
        }
        
        return $this->update_campaign($campaign_id, $update_data);
    }
    
    /**
     * Start campaign
     *
     * @param int $campaign_id Campaign ID
     * @return bool
     */
    public function start_campaign($campaign_id) {
        return $this->update_campaign($campaign_id, [
            'status' => 'running',
            'started_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Pause campaign
     *
     * @param int $campaign_id Campaign ID
     * @return bool
     */
    public function pause_campaign($campaign_id) {
        return $this->update_campaign($campaign_id, ['status' => 'paused']);
    }
    
    /**
     * Resume campaign
     *
     * @param int $campaign_id Campaign ID
     * @return bool
     */
    public function resume_campaign($campaign_id) {
        return $this->update_campaign($campaign_id, ['status' => 'running']);
    }
    
    /**
     * Cancel campaign
     *
     * @param int $campaign_id Campaign ID
     * @return bool
     */
    public function cancel_campaign($campaign_id) {
        return $this->update_campaign($campaign_id, ['status' => 'cancelled']);
    }
    
    /**
     * Get campaign logs
     *
     * @param int $campaign_id Campaign ID
     * @param array $args Query arguments
     * @return array
     */
    public function get_campaign_logs($campaign_id, $args = []) {
        $defaults = [
            'log_type' => '',
            'contact_id' => '',
            'per_page' => 100,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $logs_table = $this->wpdb->prefix . 'u43_campaign_logs';
        $contacts_table = $this->wpdb->prefix . 'u43_campaign_contacts';
        
        $where = $this->wpdb->prepare("cl.campaign_id = %d", $campaign_id);
        
        if (!empty($args['log_type'])) {
            $where .= $this->wpdb->prepare(' AND cl.log_type = %s', $args['log_type']);
        }
        
        if (!empty($args['contact_id'])) {
            $where .= $this->wpdb->prepare(' AND cl.contact_id = %d', $args['contact_id']);
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT cl.*, c.name as contact_name, c.phone as contact_phone 
            FROM $logs_table cl
            LEFT JOIN $contacts_table c ON cl.contact_id = c.id
            WHERE $where
            ORDER BY cl.$orderby
            LIMIT %d OFFSET %d";
        
        $logs = $this->wpdb->get_results($this->wpdb->prepare($query, $args['per_page'], $offset));
        
        $total = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $logs_table cl WHERE cl.campaign_id = %d",
            $campaign_id
        ));
        
        return [
            'items' => $logs,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page'])
        ];
    }
    
    /**
     * Get campaign contact delivery status
     *
     * @param int $campaign_id Campaign ID
     * @param array $args Query arguments
     * @return array
     */
    public function get_campaign_delivery_logs($campaign_id, $args = []) {
        $defaults = [
            'status' => '',
            'per_page' => 50,
            'page' => 1,
            'orderby' => 'sent_at',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $rel_table = $this->wpdb->prefix . 'u43_campaign_contacts_rel';
        $contacts_table = $this->wpdb->prefix . 'u43_campaign_contacts';
        
        $where = $this->wpdb->prepare("ccr.campaign_id = %d", $campaign_id);
        
        if (!empty($args['status'])) {
            $where .= $this->wpdb->prepare(' AND ccr.status = %s', $args['status']);
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT ccr.*, c.name as contact_name, c.phone as contact_phone 
            FROM $rel_table ccr
            LEFT JOIN $contacts_table c ON ccr.contact_id = c.id
            WHERE $where
            ORDER BY ccr.$orderby
            LIMIT %d OFFSET %d";
        
        $logs = $this->wpdb->get_results($this->wpdb->prepare($query, $args['per_page'], $offset));
        
        $total = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM $rel_table WHERE campaign_id = %d",
            $campaign_id
        ));
        
        return [
            'items' => $logs,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page'])
        ];
    }
}

