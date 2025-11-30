<?php
/**
 * Send WhatsApp Message Tool
 *
 * @package U43
 */

namespace U43\Tools\WhatsApp;

use U43\Tools\Tool_Base;
use U43\Integrations\WhatsApp\WhatsApp_API_Client;

class Send_Message extends Tool_Base {
    
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
        
        // Get message type and normalize it
        $message_type = isset($inputs['message_type']) ? strtolower(trim($inputs['message_type'])) : 'text';
        $message = isset($inputs['message']) ? trim($inputs['message']) : '';
        $template_name = isset($inputs['template_name']) ? trim($inputs['template_name']) : '';
        $template_language = isset($inputs['template_language']) ? trim($inputs['template_language']) : 'en_US';
        
        // Validate based on message type
        if ($message_type === 'text') {
            if (empty($message)) {
                throw new \Exception('Message text is required for text messages');
            }
        } elseif ($message_type === 'template') {
            if (empty($template_name)) {
                throw new \Exception('Template name is required for template messages');
            }
        } else {
            // Default to text if invalid message_type
            $message_type = 'text';
            if (empty($message)) {
                throw new \Exception('Message text is required for text messages');
            }
        }
        
        $api_client = new WhatsApp_API_Client();
        $results = [];
        $sent_to = [];
        
        // Send message to each phone number
        foreach ($phone_numbers as $phone_number) {
            if ($message_type === 'template') {
                $result = $api_client->send_template_message(
                    $phone_number,
                    $template_name,
                    [],
                    $template_language
                );
            } else {
                $result = $api_client->send_message($phone_number, $message);
            }
            
            if ($result['success']) {
                $message_id = $result['data']['messages'][0]['id'] ?? '';
                $results[] = [
                    'phone_number' => $phone_number,
                    'message_id' => $message_id,
                    'status' => 'sent',
                ];
                $sent_to[] = $phone_number;
            } else {
                $results[] = [
                    'phone_number' => $phone_number,
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
            
            $error_message = 'Failed to send WhatsApp message(s). Errors: ' . implode('; ', array_unique($error_messages));
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
            $variable_name = $inputs['phone_number_variable'];
            
            // Check if variable_value was already resolved from template ({{node_id.field}})
            // If it's an array or non-empty string, it was resolved
            if (is_array($variable_value)) {
                $phone_numbers = $variable_value;
            } elseif (is_string($variable_value) && !empty($variable_value) && $variable_value !== $variable_name) {
                // Variable was resolved - check if it's JSON array or single value
                $decoded = json_decode($variable_value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $phone_numbers = $decoded;
                } else {
                    // Single phone number
                    $phone_numbers = [$variable_value];
                }
            }
            
            // If still empty or variable wasn't resolved, search context for the variable name
            if (empty($phone_numbers)) {
                // Search through all node outputs in context
                foreach ($context as $key => $value) {
                    if ($key === 'trigger_data') {
                        continue; // Skip trigger_data
                    }
                    
                    // Check if this node output contains the variable
                    if (is_array($value)) {
                        // Check direct key match
                        if (isset($value[$variable_name])) {
                            $found_value = $value[$variable_name];
                            if (is_array($found_value)) {
                                $phone_numbers = $found_value;
                            } elseif (is_string($found_value) || is_numeric($found_value)) {
                                $phone_numbers = [(string)$found_value];
                            }
                            break;
                        }
                        
                        // Also check common field names
                        $common_fields = ['phone', 'phone_number', 'to', 'recipient', 'contact'];
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


