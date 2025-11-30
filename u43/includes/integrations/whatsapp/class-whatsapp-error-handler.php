<?php
/**
 * WhatsApp Error Handler
 *
 * @package U43
 */

namespace U43\Integrations\WhatsApp;

class WhatsApp_Error_Handler {
    
    /**
     * Error code mappings
     *
     * @var array
     */
    private static $error_codes = [
        100 => 'Invalid phone number',
        101 => 'Authentication failed',
        102 => 'Rate limit exceeded',
        103 => 'Message sending failed',
        104 => 'Webhook verification failed',
        105 => 'Connection timeout',
        106 => 'Invalid media format',
        107 => 'Template not approved',
        108 => 'Group not found',
        109 => 'Contact not found',
    ];
    
    /**
     * Get user-friendly error message
     *
     * @param int|string $error_code Error code
     * @param string $default_message Default message
     * @return string
     */
    public static function get_error_message($error_code, $default_message = 'An error occurred') {
        if (isset(self::$error_codes[$error_code])) {
            return self::$error_codes[$error_code];
        }
        
        // Handle WhatsApp API error codes
        $whatsapp_errors = [
            '131047' => 'Message template not found',
            '131026' => 'Message template parameter mismatch',
            '131031' => 'Message template language not supported',
            '131051' => 'Message template expired',
            '131000' => 'Invalid phone number format',
            '131008' => 'Rate limit exceeded',
            '131009' => 'Message too long',
            '131048' => 'Message template not approved',
        ];
        
        if (isset($whatsapp_errors[$error_code])) {
            return $whatsapp_errors[$error_code];
        }
        
        return $default_message;
    }
    
    /**
     * Handle API error
     *
     * @param array $error_response Error response from API
     * @return array
     */
    public static function handle_api_error($error_response) {
        $error_code = $error_response['error']['code'] ?? '';
        $error_message = $error_response['error']['message'] ?? 'Unknown error';
        $error_type = $error_response['error']['type'] ?? '';
        
        $user_message = self::get_error_message($error_code, $error_message);
        
        // Log error
        error_log('WhatsApp API Error: ' . $user_message . ' (Code: ' . $error_code . ', Type: ' . $error_type . ')');
        
        return [
            'success' => false,
            'message' => $user_message,
            'error_code' => $error_code,
            'error_type' => $error_type,
        ];
    }
    
    /**
     * Handle connection error
     *
     * @param \WP_Error $error WordPress error
     * @return array
     */
    public static function handle_connection_error($error) {
        $error_message = $error->get_error_message();
        
        error_log('WhatsApp Connection Error: ' . $error_message);
        
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $error_message,
            'error_code' => 105,
        ];
    }
}



