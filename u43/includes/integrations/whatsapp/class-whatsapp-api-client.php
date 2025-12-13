<?php
/**
 * WhatsApp API Client
 *
 * @package U43
 */

namespace U43\Integrations\WhatsApp;

class WhatsApp_API_Client {
    
    private $api_token;
    private $api_base_url = 'https://graph.facebook.com/v18.0';
    private $timeout = 30;
    
    /**
     * Constructor
     *
     * @param string $api_token API token
     */
    public function __construct($api_token = '') {
        $this->api_token = $api_token ?: get_option('u43_whatsapp_api_token', '');
    }
    
    /**
     * Send message
     *
     * @param string $to Phone number
     * @param string $message Message text
     * @param array $options Additional options
     * @return array
     */
    public function send_message($to, $message, $options = []) {
        $phone_number_id = get_option('u43_whatsapp_phone_number_id', '');
        
        if (empty($phone_number_id)) {
            return [
                'success' => false,
                'message' => 'Phone number ID not configured. Please configure it in WhatsApp settings.'
            ];
        }
        
        $url = $this->api_base_url . '/' . $phone_number_id . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone_number($to),
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];
        
        return $this->make_request('POST', $url, $data);
    }
    
    /**
     * Send interactive button message
     *
     * @param string $to Phone number
     * @param string $body_text Body text
     * @param array $buttons Array of button objects (max 3)
     * @param string $header_text Optional header text
     * @param string $footer_text Optional footer text
     * @return array
     */
    public function send_interactive_button_message($to, $body_text, $buttons = [], $header_text = '', $footer_text = '') {
        $phone_number_id = get_option('u43_whatsapp_phone_number_id', '');
        
        if (empty($phone_number_id)) {
            return [
                'success' => false,
                'message' => 'Phone number ID not configured. Please configure it in WhatsApp settings.'
            ];
        }
        
        if (empty($body_text)) {
            return [
                'success' => false,
                'message' => 'Body text is required'
            ];
        }
        
        if (empty($buttons) || count($buttons) > 3) {
            return [
                'success' => false,
                'message' => 'Between 1 and 3 buttons are required'
            ];
        }
        
        $url = $this->api_base_url . '/' . $phone_number_id . '/messages';
        
        // Build interactive message structure
        $interactive = [
            'type' => 'button',
            'body' => [
                'text' => $body_text
            ],
            'action' => [
                'buttons' => $buttons
            ]
        ];
        
        // Add header if provided
        if (!empty($header_text)) {
            $interactive['header'] = [
                'type' => 'text',
                'text' => $header_text
            ];
        }
        
        // Add footer if provided
        if (!empty($footer_text)) {
            $interactive['footer'] = [
                'text' => $footer_text
            ];
        }
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone_number($to),
            'type' => 'interactive',
            'interactive' => $interactive
        ];
        
        return $this->make_request('POST', $url, $data);
    }
    
    /**
     * Send template message
     *
     * @param string $to Phone number
     * @param string $template_name Template name
     * @param array $template_params Template parameters
     * @param string $language_code Language code
     * @return array
     */
    public function send_template_message($to, $template_name, $template_params = [], $language_code = 'en_US') {
        $phone_number_id = get_option('u43_whatsapp_phone_number_id', '');
        
        if (empty($phone_number_id)) {
            return [
                'success' => false,
                'message' => 'Phone number ID not configured. Please configure it in WhatsApp settings.'
            ];
        }
        
        $url = $this->api_base_url . '/' . $phone_number_id . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone_number($to),
            'type' => 'template',
            'template' => [
                'name' => $template_name,
                'language' => [
                    'code' => $language_code
                ]
            ]
        ];
        
        // Add template parameters if provided
        if (!empty($template_params)) {
            $data['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => array_map(function($param) {
                        return [
                            'type' => 'text',
                            'text' => $param
                        ];
                    }, $template_params)
                ]
            ];
        }
        
        return $this->make_request('POST', $url, $data);
    }
    
    /**
     * Send media
     *
     * @param string $to Phone number
     * @param string $media_url Media URL
     * @param string $media_type Media type (image, video, document, audio)
     * @param string $caption Caption (optional)
     * @return array
     */
    public function send_media($to, $media_url, $media_type, $caption = '') {
        $phone_number_id = get_option('u43_whatsapp_phone_number_id', '');
        
        if (empty($phone_number_id)) {
            return [
                'success' => false,
                'message' => 'Phone number ID not configured'
            ];
        }
        
        $url = $this->api_base_url . '/' . $phone_number_id . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone_number($to),
            'type' => $media_type,
            $media_type => [
                'link' => $media_url
            ]
        ];
        
        if (!empty($caption) && in_array($media_type, ['image', 'video', 'document'])) {
            $data[$media_type]['caption'] = $caption;
        }
        
        return $this->make_request('POST', $url, $data);
    }
    
    /**
     * Send template message
     *
     * @param string $to Phone number
     * @param string $template_name Template name
     * @param array $template_params Template parameters
     * @param string $language_code Language code
     * @return array
     */
    public function send_template($to, $template_name, $template_params = [], $language_code = 'en') {
        $phone_number_id = get_option('u43_whatsapp_phone_number_id', '');
        
        if (empty($phone_number_id)) {
            return [
                'success' => false,
                'message' => 'Phone number ID not configured'
            ];
        }
        
        $url = $this->api_base_url . '/' . $phone_number_id . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone_number($to),
            'type' => 'template',
            'template' => [
                'name' => $template_name,
                'language' => [
                    'code' => $language_code
                ]
            ]
        ];
        
        if (!empty($template_params)) {
            $data['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => array_map(function($param) {
                        return [
                            'type' => 'text',
                            'text' => $param
                        ];
                    }, $template_params)
                ]
            ];
        }
        
        return $this->make_request('POST', $url, $data);
    }
    
    /**
     * Get phone number info
     *
     * @param string $phone_number Phone number
     * @return array
     */
    public function get_phone_number_info($phone_number) {
        $phone_number_id = get_option('u43_whatsapp_phone_number_id', '');
        
        if (empty($phone_number_id)) {
            return [
                'success' => false,
                'message' => 'Phone number ID not configured'
            ];
        }
        
        $url = $this->api_base_url . '/' . $phone_number_id;
        
        return $this->make_request('GET', $url);
    }
    
    /**
     * Make HTTP request
     *
     * @param string $method HTTP method
     * @param string $url URL
     * @param array $data Request data
     * @return array
     */
    public function make_request($method, $url, $data = []) {
        $args = [
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json'
            ]
        ];
        
        if ($method === 'POST' && !empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);
        
        if ($status_code >= 200 && $status_code < 300) {
            return [
                'success' => true,
                'data' => $decoded_body
            ];
        }
        
        // Extract error message with more detail
        $error_message = 'Request failed';
        $error_code = $status_code;
        
        if (isset($decoded_body['error'])) {
            $error_message = $decoded_body['error']['message'] ?? 'Request failed';
            $error_code = $decoded_body['error']['code'] ?? $status_code;
            
            // Check for specific error types
            if (isset($decoded_body['error']['type'])) {
                $error_type = $decoded_body['error']['type'];
                
                // Handle authentication errors
                if (strpos($error_message, 'Session has expired') !== false || 
                    strpos($error_message, 'expired') !== false ||
                    $error_type === 'OAuthException') {
                    $error_message = 'WhatsApp API authentication failed. Your access token has expired. Please update your API token in WhatsApp settings. Original error: ' . $error_message;
                }
                
                // Handle rate limiting
                if ($error_type === 'OAuthException' && strpos($error_message, 'rate limit') !== false) {
                    $error_message = 'WhatsApp API rate limit exceeded. Please wait before sending more messages. Original error: ' . $error_message;
                }
            }
        }
        
        return [
            'success' => false,
            'message' => $error_message,
            'error_code' => $error_code,
            'raw_error' => $decoded_body['error'] ?? null
        ];
    }
    
    /**
     * Format phone number
     *
     * @param string $phone_number Phone number
     * @param bool $for_api Whether formatting for API (removes +) or for display (keeps +)
     * @return string Formatted phone number
     */
    public function format_phone_number($phone_number, $for_api = true) {
        // Remove all non-digit characters first
        $phone_number = preg_replace('/[^\d]/', '', (string)$phone_number);
        
        if (empty($phone_number)) {
            return $phone_number;
        }
        
        // Add + prefix for E.164 format
        $formatted = '+' . $phone_number;
        
        // WhatsApp Cloud API requires phone numbers without + prefix
        // Return digits only if formatting for API
        if ($for_api) {
            return $phone_number;
        }
        
        // Return with + prefix for display/storage
        return $formatted;
    }
}

