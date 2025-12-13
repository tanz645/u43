<?php
/**
 * Send WhatsApp Button Message Tool
 *
 * @package U43
 */

namespace U43\Tools\WhatsApp;

use U43\Tools\Tool_Base;
use U43\Integrations\WhatsApp\WhatsApp_API_Client;

class Send_Button_Message extends Tool_Base {
    
    /**
     * Execute the tool
     *
     * @param array $inputs Input parameters
     * @param array $context Workflow context (for accessing previous node outputs)
     * @return array
     * @throws \Exception
     */
    public function execute($inputs, $context = []) {
        // Get phone numbers - direct input has higher precedence
        $phone_numbers = $this->get_phone_numbers($inputs, $context);
        
        if (empty($phone_numbers)) {
            throw new \Exception('Phone numbers are required. Provide either phone_numbers array or phone_number_variable from previous node.');
        }
        
        // Get message components
        $header_text = isset($inputs['header_text']) ? trim($inputs['header_text']) : '';
        $body_text = isset($inputs['body_text']) ? trim($inputs['body_text']) : '';
        $footer_text = isset($inputs['footer_text']) ? trim($inputs['footer_text']) : '';
        
        // Validate body text
        if (empty($body_text)) {
            throw new \Exception('Body text is required');
        }
        
        // Get buttons
        $buttons = $this->get_buttons($inputs, $context);
        
        if (empty($buttons) || count($buttons) < 1) {
            throw new \Exception('At least one button is required');
        }
        
        if (count($buttons) > 3) {
            throw new \Exception('Maximum 3 buttons allowed');
        }
        
        $api_client = new WhatsApp_API_Client();
        $results = [];
        $sent_to = [];
        
        // Send message to each phone number
        foreach ($phone_numbers as $phone_number) {
            $result = $api_client->send_interactive_button_message(
                $phone_number,
                $body_text,
                $buttons,
                $header_text,
                $footer_text
            );
            
            // Format phone number with + prefix for storage/display
            $formatted_phone = $api_client->format_phone_number($phone_number, false);
            
            if ($result['success']) {
                $message_id = $result['data']['messages'][0]['id'] ?? '';
                $results[] = [
                    'phone_number' => $formatted_phone,
                    'message_id' => $message_id,
                    'status' => 'sent',
                ];
                $sent_to[] = $formatted_phone;
            } else {
                $results[] = [
                    'phone_number' => $formatted_phone,
                    'status' => 'failed',
                    'error' => $result['message'] ?? 'Failed to send message',
                ];
            }
        }
        
        // Return summary
        $success_count = count(array_filter($results, function($r) { return $r['status'] === 'sent'; }));
        $failed_count = count($results) - $success_count;
        
        $final_status = $failed_count === 0 ? 'sent' : ($success_count > 0 ? 'partial' : 'failed');
        
        // If all messages failed, throw an exception so the node is marked as failed
        if ($final_status === 'failed') {
            $error_messages = array_map(function($r) {
                return $r['error'] ?? 'Unknown error';
            }, array_filter($results, function($r) { return $r['status'] === 'failed'; }));
            
            $error_message = 'Failed to send WhatsApp button message(s). Errors: ' . implode('; ', array_unique($error_messages));
            throw new \Exception($error_message);
        }
        
        return [
            'message_id' => $results[0]['message_id'] ?? '',
            'status' => $final_status,
            'sent_at' => time(),
            'sent_to' => $sent_to,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'success' => $success_count,
                'failed' => $failed_count,
            ],
        ];
    }
    
    /**
     * Get buttons from inputs or context
     *
     * @param array $inputs Tool inputs (already resolved by executor)
     * @param array $context Workflow context
     * @return array Array of button objects
     */
    private function get_buttons($inputs, $context = []) {
        $buttons = [];
        
        // Get buttons from direct array input
        if (!empty($inputs['buttons']) && is_array($inputs['buttons'])) {
            $buttons = $inputs['buttons'];
        }
        
        // Validate and normalize buttons
        $normalized_buttons = [];
        $button_colors = ['#3B82F6', '#10B981', '#F59E0B']; // Blue, Green, Orange
        
        foreach ($buttons as $index => $button) {
            if (!is_array($button)) {
                continue;
            }
            
            // Ensure required fields
            if (empty($button['id']) || empty($button['title'])) {
                continue;
            }
            
            // Default to reply type
            $button['type'] = $button['type'] ?? 'reply';
            
            // Add color if not set (different color for each button)
            if (empty($button['color']) && isset($button_colors[$index])) {
                $button['color'] = $button_colors[$index];
            }
            
            $normalized_buttons[] = [
                'type' => $button['type'],
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['title'],
                ],
            ];
        }
        
        return $normalized_buttons;
    }
    
    /**
     * Get phone numbers from inputs or context
     *
     * @param array $inputs Tool inputs (already resolved by executor)
     * @param array $context Workflow context
     * @return array Array of phone numbers
     */
    private function get_phone_numbers($inputs, $context = []) {
        $phone_numbers = [];
        
        // Priority 1: Direct phone_numbers array input (highest precedence)
        if (!empty($inputs['phone_numbers']) && is_array($inputs['phone_numbers'])) {
            $phone_numbers = $inputs['phone_numbers'];
        }
        // Priority 2: Variable from previous node
        elseif (!empty($inputs['phone_number_variable'])) {
            $variable_value = $inputs['phone_number_variable'];
            $original_variable = $variable_value;
            
            // Check if variable_value was already resolved from template ({{node_id.field}})
            // If it doesn't contain {{ }}, it was already resolved by the executor
            $is_resolved = !is_string($variable_value) || strpos($variable_value, '{{') === false;
            
            if (is_array($variable_value)) {
                $phone_numbers = $variable_value;
            } elseif ($is_resolved && is_string($variable_value) && !empty($variable_value)) {
                // Variable was resolved - check if it's JSON array or single value
                $decoded = json_decode($variable_value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $phone_numbers = $decoded;
                } else {
                    // Single phone number
                    $phone_numbers = [$variable_value];
                }
            }
            
            // If still empty or variable wasn't resolved, try to resolve it manually from context
            if (empty($phone_numbers) && !$is_resolved && is_string($original_variable)) {
                // Try to resolve the template manually (e.g., {{trigger_data.content}})
                if (preg_match('/\{\{([^}]+)\}\}/', $original_variable, $matches)) {
                    $var_path = trim($matches[1]);
                    $parts = explode('.', $var_path);
                    $var_value = $context;
                    
                    foreach ($parts as $part) {
                        if (is_array($var_value) && isset($var_value[$part])) {
                            $var_value = $var_value[$part];
                        } else {
                            $var_value = null;
                            break;
                        }
                    }
                    
                    if ($var_value !== null) {
                        if (is_array($var_value)) {
                            $phone_numbers = $var_value;
                        } elseif (is_string($var_value) || is_numeric($var_value)) {
                            $phone_numbers = [(string)$var_value];
                        }
                    }
                }
            }
            
            // If still empty, search context for common phone number fields
            if (empty($phone_numbers)) {
                // Search through all node outputs in context (including trigger_data)
                foreach ($context as $key => $value) {
                    if (is_array($value)) {
                        // Check common field names
                        $common_fields = ['phone', 'phone_number', 'to', 'recipient', 'contact', 'content'];
                        foreach ($common_fields as $field) {
                            if (isset($value[$field])) {
                                $found_value = $value[$field];
                                if (is_array($found_value)) {
                                    $phone_numbers = $found_value;
                                } elseif (is_string($found_value) || is_numeric($found_value)) {
                                    $phone_numbers = [(string)$found_value];
                                }
                                break 2; // Break both loops
                            }
                        }
                    }
                }
            }
        }
        
        // Clean and format phone numbers (remove all non-digit characters)
        return array_map(function($phone) {
            // Remove all non-digit characters
            $phone = preg_replace('/[^\d]/', '', (string)$phone);
            return $phone;
        }, array_filter($phone_numbers, function($phone) {
            return !empty($phone);
        }));
    }
}
