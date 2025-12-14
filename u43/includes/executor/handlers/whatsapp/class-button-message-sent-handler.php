<?php
/**
 * WhatsApp Button Message Sent Handler
 *
 * Handles stopping execution after button message is sent
 * (waits for button click before continuing)
 *
 * @package U43
 */

namespace U43\Executor\Handlers\WhatsApp;

use U43\Executor\Handlers\Node_Handler_Base;

class Button_Message_Sent_Handler extends Node_Handler_Base {
    
    /**
     * Check if this handler can handle the given node
     *
     * @param array $node Node data
     * @param array $output Node output
     * @return bool
     */
    public function can_handle($node, $output) {
        // Handle button message nodes that have been sent (no button_id yet - waiting for click)
        return $node['type'] === 'action' 
            && isset($node['config']['tool_id']) 
            && $node['config']['tool_id'] === 'whatsapp_send_button_message' 
            && is_array($output) 
            && !isset($output['button_id']); // No button_id means message was just sent, not clicked
    }
    
    /**
     * Handle button message node - stop execution and wait for button click
     *
     * @param array $node Node data
     * @param array $output Node output
     * @param array $next_nodes Connected nodes
     * @param array $all_nodes All nodes in workflow
     * @param array $context Execution context
     * @param callable $execute_node_callback Callback to execute a node
     * @param callable $find_node_callback Callback to find a node by ID
     * @return array Result with 'routed' (bool) and 'context' (array)
     */
    public function handle($node, $output, $next_nodes, $all_nodes, $context, $execute_node_callback, $find_node_callback) {
        // If there are connected nodes, pause execution and wait for button click
        // The workflow will continue when a button is clicked via webhook
        // If no connected nodes, execution completes normally
        
        if (!empty($next_nodes)) {
            // Don't execute connected nodes - wait for button click
            return [
                'routed' => false, // Don't route - wait for button click
                'context' => $context,
                'has_errors' => false,
                'error_message' => '',
                'paused' => true, // Indicate execution is paused
            ];
        }
        
        // No connected nodes - execution completes normally
        return [
            'routed' => false,
            'context' => $context,
            'has_errors' => false,
            'error_message' => '',
            'paused' => false,
        ];
    }
}

