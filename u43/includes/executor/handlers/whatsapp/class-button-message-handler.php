<?php
/**
 * WhatsApp Button Message Handler
 *
 * @package U43
 */

namespace U43\Executor\Handlers\WhatsApp;

use U43\Executor\Handlers\Node_Handler_Base;

class Button_Message_Handler extends Node_Handler_Base {
    
    /**
     * Check if this handler can handle the given node
     *
     * @param array $node Node data
     * @param array $output Node output
     * @return bool
     */
    public function can_handle($node, $output) {
        return $node['type'] === 'action' 
            && isset($node['config']['tool_id']) 
            && $node['config']['tool_id'] === 'whatsapp_send_button_message' 
            && is_array($output) 
            && isset($output['button_id']);
    }
    
    /**
     * Handle button message node routing
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
        $button_id = $output['button_id'];
        $routed = false;
        $has_errors = false;
        $error_message = '';
        
        // Filter nodes based on button_id matching sourceHandle
        foreach ($next_nodes as $edge_data) {
            $next_node_id = is_array($edge_data) ? $edge_data['node_id'] : $edge_data;
            $source_handle = is_array($edge_data) ? ($edge_data['sourceHandle'] ?? null) : null;
            
            // Check if this edge matches the button_id
            if ($source_handle === $button_id) {
                // Button ID matches sourceHandle - route to this node
                $routed = true;
                $next_node = $find_node_callback($all_nodes, $next_node_id);
                if ($next_node) {
                    try {
                        // Update button message node output in context with button ID as a field
                        // This allows {{parents.action.btn1}} to resolve correctly
                        $node_id = $node['id'];
                        if (isset($context[$node_id]) && is_array($context[$node_id])) {
                            $button_id = $output['button_id'] ?? '';
                            $button_title = $output['button_title'] ?? $button_id;
                            // Add button ID as a field with button title as value so {{parents.action.btn1}} resolves to "Dhaka"
                            $context[$node_id][$button_id] = $button_title;
                        }
                        
                        // Pass button details to connected node
                        $button_context = $context;
                        $button_context['button_data'] = [
                            'button_id' => $output['button_id'] ?? '',
                            'button_title' => $output['button_title'] ?? '',
                            'interactive_type' => $output['interactive_type'] ?? 'button_reply',
                        ];
                        $execute_node_callback($next_node, $button_context);
                    } catch (\Exception $child_exception) {
                        $has_errors = true;
                        if (empty($error_message)) {
                            $error_message = "Node '{$next_node_id}' failed: " . $child_exception->getMessage();
                        }
                    }
                }
            }
        }
        
        return [
            'routed' => $routed,
            'context' => $context,
            'has_errors' => $has_errors,
            'error_message' => $error_message,
        ];
    }
}

