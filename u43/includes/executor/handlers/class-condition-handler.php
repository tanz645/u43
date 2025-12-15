<?php
/**
 * Condition Node Handler
 *
 * @package U43
 */

namespace U43\Executor\Handlers;

class Condition_Handler extends Node_Handler_Base {
    
    /**
     * Check if this handler can handle the given node
     *
     * @param array $node Node data
     * @param array $output Node output
     * @return bool
     */
    public function can_handle($node, $output) {
        return $node['type'] === 'condition' && is_array($output) && isset($output['result']);
    }
    
    /**
     * Handle condition node routing
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
        $condition_result = $output['result'];
        $routed = false;
        $has_errors = false;
        $error_message = '';
        
        // Filter nodes based on condition result and edge sourceHandle
        foreach ($next_nodes as $edge_data) {
            $next_node_id = is_array($edge_data) ? $edge_data['node_id'] : $edge_data;
            $source_handle = is_array($edge_data) ? ($edge_data['sourceHandle'] ?? null) : null;
            
            // Check if this edge matches the condition result
            // sourceHandle "true" means condition passed, "false" means condition failed
            $should_execute = false;
            if ($condition_result === true && ($source_handle === 'true' || $source_handle === null)) {
                // Condition is true, execute "true" branch or edges without handle (backward compatibility)
                $should_execute = true;
            } elseif ($condition_result === false && $source_handle === 'false') {
                // Condition is false, execute "false" branch
                $should_execute = true;
            } elseif ($source_handle === null) {
                // Backward compatibility: if no handle specified, execute all edges
                $should_execute = true;
            }
            
            if ($should_execute) {
                $routed = true;
                $next_node = $find_node_callback($all_nodes, $next_node_id);
                if ($next_node) {
                    try {
                        $execute_node_callback($next_node, $context);
                    } catch (\Exception $child_exception) {
                        $has_errors = true;
                        if (empty($error_message)) {
                            $error_message = "Node '{$next_node_id}' failed: " . $child_exception->getMessage();
                        }
                    }
                }
            }
        }
        
        // Log warning if condition didn't route to any node
        if (!$routed && !empty($next_nodes)) {
            $warning_msg = sprintf(
                "Condition node evaluated to %s but no matching branch was found. Available handles: %s",
                $condition_result ? 'true' : 'false',
                implode(', ', array_filter(array_map(function($edge) {
                    return is_array($edge) ? ($edge['sourceHandle'] ?? 'none') : 'none';
                }, $next_nodes)))
            );
            error_log("U43: {$warning_msg}");
            // Update node output to include routing info
            $output['_routing_warning'] = $warning_msg;
            $context[$node['id']] = $output;
        }
        
        return [
            'routed' => $routed,
            'context' => $context,
            'has_errors' => $has_errors,
            'error_message' => $error_message,
        ];
    }
}

