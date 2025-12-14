<?php
/**
 * WhatsApp Button Message Service
 *
 * Handles button message mapping and continuation logic
 *
 * @package U43
 */

namespace U43\Executor\Handlers\WhatsApp;

class Button_Message_Service {
    
    /**
     * Store button message mapping for routing button clicks
     *
     * @param string $node_id Node ID
     * @param array $output Node output
     * @param array $node Node data
     * @param int $workflow_id Workflow ID
     * @param int $execution_id Execution ID
     */
    public static function store_mapping($node_id, $output, $node, $workflow_id, $execution_id) {
        global $wpdb;
        
        if (empty($output['message_id'])) {
            return;
        }
        
        // Get button IDs from node config
        $buttons = $node['config']['inputs']['buttons'] ?? [];
        $button_ids = [];
        
        if (is_array($buttons)) {
            foreach ($buttons as $button) {
                if (is_array($button) && !empty($button['id'])) {
                    $button_ids[] = $button['id'];
                }
            }
        }
        
        if (empty($button_ids)) {
            return;
        }
        
        // Store mapping
        $wpdb->insert(
            $wpdb->prefix . 'u43_button_message_mappings',
            [
                'message_id' => $output['message_id'],
                'workflow_id' => $workflow_id,
                'execution_id' => $execution_id,
                'node_id' => $node_id,
                'button_ids' => json_encode($button_ids),
            ],
            ['%s', '%d', '%d', '%s', '%s']
        );
    }
    
    /**
     * Log button continuation start
     *
     * @param int $execution_id Execution ID
     * @param string $node_id Node ID
     * @param string $button_id Button ID
     * @param array $button_data Button data
     */
    public static function log_continuation_start($execution_id, $node_id, $button_id, $button_data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'u43_node_logs',
            [
                'execution_id' => $execution_id,
                'node_id' => $node_id . '_button_continuation',
                'node_type' => 'button_routing',
                'node_title' => "Button Click: {$button_id}",
                'status' => 'running',
                'input_data' => json_encode([
                    'button_id' => $button_id,
                    'button_title' => $button_data['button_title'] ?? '',
                    'interactive_type' => $button_data['interactive_type'] ?? 'button_reply',
                ]),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Log button continuation error
     *
     * @param int $execution_id Execution ID
     * @param string $node_id Node ID
     * @param string $error_message Error message
     * @param array $context Additional context
     */
    public static function log_continuation_error($execution_id, $node_id, $error_message, $context = []) {
        global $wpdb;
        
        // Check if log entry exists
        $existing_log = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}u43_node_logs 
             WHERE execution_id = %d AND node_id = %s AND node_type = 'button_routing' 
             ORDER BY id DESC LIMIT 1",
            $execution_id,
            $node_id . '_button_continuation'
        ));
        
        if ($existing_log) {
            // Update existing log
            $wpdb->update(
                $wpdb->prefix . 'u43_node_logs',
                [
                    'status' => 'failed',
                    'completed_at' => current_time('mysql'),
                    'error_message' => $error_message,
                    'output_data' => json_encode(array_merge([
                        'error' => true,
                        'message' => $error_message,
                    ], $context)),
                ],
                ['id' => $existing_log->id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            // Create new log entry
            $wpdb->insert(
                $wpdb->prefix . 'u43_node_logs',
                [
                    'execution_id' => $execution_id,
                    'node_id' => $node_id . '_button_continuation',
                    'node_type' => 'button_routing',
                    'node_title' => 'Button Click Routing',
                    'status' => 'failed',
                    'completed_at' => current_time('mysql'),
                    'error_message' => $error_message,
                    'output_data' => json_encode(array_merge([
                        'error' => true,
                        'message' => $error_message,
                    ], $context)),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
        }
    }
}

