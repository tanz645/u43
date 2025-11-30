<?php
/**
 * WhatsApp Admin Handler
 *
 * Handles all WhatsApp-related AJAX requests
 *
 * @package U43
 */

namespace U43\Admin\Handlers;

class WhatsApp_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_u43_test_whatsapp_connection', [$this, 'ajax_test_whatsapp_connection']);
        add_action('wp_ajax_u43_generate_whatsapp_qr', [$this, 'ajax_generate_whatsapp_qr']);
    }
    
    /**
     * AJAX: Test WhatsApp connection
     */
    public function ajax_test_whatsapp_connection() {
        // Verify nonce - check both 'nonce' and '_ajax_nonce' parameters
        $nonce = $_POST['nonce'] ?? $_POST['_ajax_nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'u43_test_whatsapp')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        try {
            $connection = new \U43\Integrations\WhatsApp\WhatsApp_Connection();
            $result = $connection->test_connection();
            
            if ($result['success']) {
                wp_send_json_success(['message' => $result['message']]);
            } else {
                wp_send_json_error(['message' => $result['message'] ?? 'Connection test failed']);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Generate WhatsApp QR code
     */
    public function ajax_generate_whatsapp_qr() {
        // Verify nonce - check both 'nonce' and '_ajax_nonce' parameters
        $nonce = $_POST['nonce'] ?? $_POST['_ajax_nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'u43_whatsapp_qr')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        try {
            $connection = new \U43\Integrations\WhatsApp\WhatsApp_Connection();
            $result = $connection->generate_qr_code();
            
            if ($result['success']) {
                wp_send_json_success([
                    'qr_code' => $result['qr_code'],
                    'message' => $result['message'] ?? 'QR code generated successfully'
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result['message'] ?? 'Failed to generate QR code'
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
    }
}

