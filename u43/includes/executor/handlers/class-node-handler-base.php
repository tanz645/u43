<?php
/**
 * Base Node Handler
 *
 * @package U43
 */

namespace U43\Executor\Handlers;

abstract class Node_Handler_Base {
    
    /**
     * Check if this handler can handle the given node
     *
     * @param array $node Node data
     * @param array $output Node output
     * @return bool
     */
    abstract public function can_handle($node, $output);
    
    /**
     * Handle node routing/execution
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
    abstract public function handle($node, $output, $next_nodes, $all_nodes, $context, $execute_node_callback, $find_node_callback);
}

