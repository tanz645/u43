<?php
/**
 * Data Cleanup Class
 *
 * @package U43
 */

namespace U43;

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
            ];
            
            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$table}");
            }
            
            // Delete all plugin options
            $options = [
                'u43_openai_api_key',
                'u43_whatsapp_phone_number',
                'u43_whatsapp_phone_number_id',
                'u43_whatsapp_api_token',
                'u43_whatsapp_business_id',
                'u43_whatsapp_webhook_url',
                'u43_whatsapp_webhook_verify_token',
                'u43_whatsapp_auth_method',
                'u43_whatsapp_connection_status',
                'u43_whatsapp_qr_code_image',
                'u43_whatsapp_qr_code_session',
            ];
            
            foreach ($options as $option) {
                delete_option($option);
            }
            
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

