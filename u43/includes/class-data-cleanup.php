<?php
/**
 * Data Cleanup Class
 *
 * @package U43
 */

namespace U43;

use U43\Config\Settings_Manager;

class Data_Cleanup {
    
    /**
     * Remove all plugin data
     *
     * @return array Result with success status and message
     */
    public static function remove_all_data() {
        global $wpdb;
        
        $errors = [];
        
        try {
            // Delete all database tables
            $tables = [
                $wpdb->prefix . 'u43_workflows',
                $wpdb->prefix . 'u43_executions',
                $wpdb->prefix . 'u43_node_logs',
                $wpdb->prefix . 'u43_credentials',
                $wpdb->prefix . 'u43_button_message_mappings',
                $wpdb->prefix . 'u43_settings',
            ];
            
            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$table}");
            }
            
            // Delete all plugin settings from settings table
            Settings_Manager::delete_all();
            
            // Delete all transients
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_u43_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_u43_%'");
            
            // Delete all site transients (multisite)
            if (is_multisite()) {
                $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_u43_%'");
                $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_timeout_u43_%'");
            }
            
            // Clear any cached data
            wp_cache_flush();
            
            return [
                'success' => true,
                'message' => __('All plugin data has been removed successfully.', 'u43'),
            ];
            
        } catch (\Exception $e) {
            error_log('U43: Error removing plugin data - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => __('Error removing plugin data: ', 'u43') . $e->getMessage(),
            ];
        }
    }
}

