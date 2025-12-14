<?php
/**
 * WhatsApp Button Continuation Service
 *
 * Handles workflow continuation from button clicks
 *
 * @package U43
 */

namespace U43\Executor\Handlers\WhatsApp;

use U43\Flow_Manager;
use U43\Executor;

class Button_Continuation_Service {
    
    /**
     * Continue workflow execution from a button message node when button is clicked
     *
     * @param string $message_id WhatsApp message ID
     * @param string $button_id Button ID that was clicked
     * @param array $button_data Button data (button_id, button_title, interactive_type)
     * @return int|false Execution ID or false on failure
     */
    public static function continue_from_button_message($message_id, $button_id, $button_data = []) {
        $executor = new Executor(U43()->get_tools_registry(), U43()->get_agents_registry());
        return self::continue_with_executor($executor, $message_id, $button_id, $button_data);
    }
    
    /**
     * Continue workflow execution with provided executor instance
     *
     * @param Executor $executor Executor instance
     * @param string $message_id WhatsApp message ID
     * @param string $button_id Button ID that was clicked
     * @param array $button_data Button data (button_id, button_title, interactive_type)
     * @return int|false Execution ID or false on failure
     */
    public static function continue_with_executor(Executor $executor, $message_id, $button_id, $button_data = []) {
        global $wpdb;
        
        // Find the mapping
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}u43_button_message_mappings WHERE message_id = %s ORDER BY id DESC LIMIT 1",
            $message_id
        ));
        
        if (!$mapping) {
            $error_msg = "No mapping found for message_id: {$message_id}. Button click cannot be routed to workflow.";
            error_log("U43: {$error_msg}");
            return false;
        }
        
        // Get workflow
        $flow_manager = new Flow_Manager();
        $workflow = $flow_manager->get_workflow($mapping->workflow_id);
        
        if (!$workflow || $workflow->status !== 'published') {
            $error_msg = "Workflow {$mapping->workflow_id} not found or not published. Cannot continue button click routing.";
            error_log("U43: {$error_msg}");
            Button_Message_Service::log_continuation_error($mapping->execution_id, $mapping->node_id, $error_msg, [
                'message_id' => $message_id,
                'button_id' => $button_id,
            ]);
            return false;
        }
        
        // Get execution context
        $execution = $flow_manager->get_execution($mapping->execution_id);
        if (!$execution) {
            $error_msg = "Execution {$mapping->execution_id} not found. Cannot continue button click routing.";
            error_log("U43: {$error_msg}");
            return false;
        }
        
        // Reconstruct context from execution logs
        $context = ['trigger_data' => $execution->trigger_data ?? []];
        
        // Get node logs to reconstruct context
        $node_logs = $flow_manager->get_node_logs($mapping->execution_id);
        foreach ($node_logs as $log) {
            if ($log->output_data) {
                $output = json_decode($log->output_data, true);
                if ($output) {
                    $context[$log->node_id] = $output;
                }
            }
        }
        
        // Update button message node output with button details for routing
        $context[$mapping->node_id] = array_merge(
            $context[$mapping->node_id] ?? [],
            [
                'button_id' => $button_id,
                'button_title' => $button_data['button_title'] ?? '',
                'interactive_type' => $button_data['interactive_type'] ?? 'button_reply',
            ]
        );
        
        // Build execution graph
        $nodes = $workflow->workflow_data['nodes'] ?? [];
        $edges = $workflow->workflow_data['edges'] ?? [];
        
        // Set execution context on executor
        $executor->set_execution_context($mapping->execution_id, $mapping->workflow_id);
        
        // Build graph using executor's method
        $graph = self::build_graph($nodes, $edges);
        
        // Find the button message node
        $button_node = self::find_node_by_id($nodes, $mapping->node_id);
        if (!$button_node) {
            $error_msg = "Button message node {$mapping->node_id} not found in workflow. Cannot route button click.";
            error_log("U43: {$error_msg}");
            Button_Message_Service::log_continuation_error($mapping->execution_id, $mapping->node_id, $error_msg, [
                'message_id' => $message_id,
                'button_id' => $button_id,
            ]);
            return false;
        }
        
        // Route to connected nodes based on button_id (without re-executing the button node)
        $next_nodes = $graph[$mapping->node_id] ?? [];
        $start_time = microtime(true);
        
        // Log button continuation attempt
        Button_Message_Service::log_continuation_start($mapping->execution_id, $mapping->node_id, $button_id, $button_data);
        
        try {
            $routed = false;
            $has_failed_nodes = false;
            $first_error_message = '';
            
            // Filter nodes based on button_id matching sourceHandle
            foreach ($next_nodes as $edge_data) {
                $next_node_id = is_array($edge_data) ? $edge_data['node_id'] : $edge_data;
                $source_handle = is_array($edge_data) ? ($edge_data['sourceHandle'] ?? null) : null;
                
                // Check if this edge matches the button_id
                if ($source_handle === $button_id) {
                    // Button ID matches sourceHandle - route to this node
                    $routed = true;
                    $next_node = self::find_node_by_id($nodes, $next_node_id);
                    if ($next_node) {
                        try {
                            // Pass button details to connected node
                            $button_context = $context;
                            $button_context['button_data'] = [
                                'button_id' => $button_id,
                                'button_title' => $button_data['button_title'] ?? '',
                                'interactive_type' => $button_data['interactive_type'] ?? 'button_reply',
                            ];
                            
                            // Use executor's public method to continue execution
                            // This will execute the node and all connected nodes recursively
                            $executor->continue_execution_from_node($next_node, $button_context, $graph, $nodes);
                        } catch (\Exception $child_exception) {
                            $has_failed_nodes = true;
                            if (empty($first_error_message)) {
                                $first_error_message = "Node '{$next_node_id}' failed: " . $child_exception->getMessage();
                            }
                        }
                    }
                }
            }
            
            // If no node was routed, log warning and mark as failed
            if (!$routed) {
                $error_msg = "No connected node found for button_id '{$button_id}'. Make sure a node is connected to this button's output handle.";
                error_log("U43: {$error_msg}");
                Button_Message_Service::log_continuation_error($mapping->execution_id, $mapping->node_id, $error_msg, [
                    'message_id' => $message_id,
                    'button_id' => $button_id,
                    'available_handles' => array_map(function($edge) {
                        return is_array($edge) ? ($edge['sourceHandle'] ?? null) : null;
                    }, $next_nodes),
                ]);
                $has_failed_nodes = true;
                $first_error_message = $error_msg;
            }
            
            // Check executor's state after execution completes
            $executor_state = $executor->get_execution_state();
            
            // Combine local and executor failure states
            if ($executor_state['has_failed_nodes'] || $has_failed_nodes) {
                $has_failed_nodes = true;
                if (empty($first_error_message) && !empty($executor_state['first_error_message'])) {
                    $first_error_message = $executor_state['first_error_message'];
                }
            }
            
            // Update executor's error state if we have local errors
            if ($has_failed_nodes && !empty($first_error_message)) {
                // Set executor's error state so finalize_continuation can use it
                $reflection = new \ReflectionClass($executor);
                $error_msg_prop = $reflection->getProperty('first_error_message');
                $error_msg_prop->setAccessible(true);
                $has_failed_prop = $reflection->getProperty('has_failed_nodes');
                $has_failed_prop->setAccessible(true);
                
                if (empty($executor_state['first_error_message'])) {
                    $error_msg_prop->setValue($executor, $first_error_message);
                }
                $has_failed_prop->setValue($executor, true);
            }
            
            // Finalize execution and update status
            $success = $executor->finalize_continuation($mapping->execution_id, $start_time);
            
            return $success ? $mapping->execution_id : false;
        } catch (\Exception $e) {
            $duration_ms = (microtime(true) - $start_time) * 1000;
            $error_msg = "Workflow continuation failed: " . $e->getMessage();
            self::update_execution_status($mapping->execution_id, 'failed', $error_msg, $duration_ms);
            error_log("U43: {$error_msg}");
            Button_Message_Service::log_continuation_error($mapping->execution_id, $mapping->node_id, $error_msg, [
                'message_id' => $message_id,
                'button_id' => $button_id,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

