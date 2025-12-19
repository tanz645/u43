<?php
/**
 * Workflow Executor
 *
 * @package U43
 */

namespace U43;

use U43\Executor\Handlers\Condition_Handler;
use U43\Executor\Handlers\WhatsApp\Button_Message_Handler;
use U43\Executor\Handlers\WhatsApp\Button_Message_Sent_Handler;
use U43\Executor\Handlers\WhatsApp\Button_Message_Service;
use U43\Executor\Handlers\WhatsApp\Button_Continuation_Service;

class Executor {
    
    private $tools_registry;
    private $agents_registry;
    private $execution_id;
    private $workflow_id;
    private $has_failed_nodes = false;
    private $first_error_message = '';
    private $node_handlers = [];
    private $is_paused = false;
    
    /**
     * Constructor
     *
     * @param Registry\Tools_Registry $tools_registry
     * @param Registry\Agents_Registry $agents_registry
     */
    public function __construct($tools_registry, $agents_registry) {
        $this->tools_registry = $tools_registry;
        $this->agents_registry = $agents_registry;
        
        // Register node handlers (order matters - more specific handlers first)
        $this->node_handlers = [
            new Condition_Handler(),
            new Button_Message_Sent_Handler(), // Check for sent button messages first (no button_id)
            new Button_Message_Handler(), // Then check for button clicks (has button_id)
        ];
    }
    
    /**
     * Set execution context (for continuation scenarios)
     *
     * @param int $execution_id Execution ID
     * @param int $workflow_id Workflow ID
     */
    public function set_execution_context($execution_id, $workflow_id) {
        $this->execution_id = $execution_id;
        $this->workflow_id = $workflow_id;
    }
    
    /**
     * Execute a workflow
     *
     * @param object $workflow Workflow object
     * @param array $trigger_data Trigger data
     * @return int|false Execution ID or false on failure
     */
    public function execute($workflow, $trigger_data = []) {
        global $wpdb;
        
        // Create execution record
        $execution_id = $this->create_execution($workflow->id, $trigger_data);
        if (!$execution_id) {
            return false;
        }
        
        $this->execution_id = $execution_id;
        $this->workflow_id = $workflow->id;
        $start_time = microtime(true);
        
        // Track if any node failed
        $this->has_failed_nodes = false;
        $this->first_error_message = '';
        
        $nodes = $workflow->workflow_data['nodes'] ?? [];
        $edges = $workflow->workflow_data['edges'] ?? [];
        
        try {
            // Build execution graph
            $graph = $this->build_graph($nodes, $edges);
            
            // Find trigger node
            $trigger_node = $this->find_trigger_node($nodes);
            if (!$trigger_node) {
                throw new \Exception('No trigger node found');
            }
            
            // Execute workflow
            $context = ['trigger_data' => $trigger_data];
            $this->execute_node($trigger_node, $context, $graph, $nodes);
            
            // Calculate duration
            $duration_ms = (microtime(true) - $start_time) * 1000;
            
            // Check if execution is paused (waiting for button click)
            if ($this->is_paused) {
                // Mark execution as running (it will continue when button is clicked)
                // Don't mark as completed - leave it in running state
                error_log("U43: Workflow execution paused - waiting for button click. Execution ID: {$execution_id}");
                // Don't update status - keep it as 'running' so it can continue later
                return $execution_id;
            }
            
            // Check if any node failed
            if ($this->has_failed_nodes) {
                // Mark workflow as failed if any node failed
                $this->update_execution_status($execution_id, 'failed', $this->first_error_message, $duration_ms);
                error_log('U43: Workflow execution failed - one or more nodes failed');
                return false;
            }
            
            // Update execution status
            $this->update_execution_status($execution_id, 'success', '', $duration_ms);
            
            return $execution_id;
        } catch (\Exception $e) {
            $duration_ms = (microtime(true) - $start_time) * 1000;
            $this->update_execution_status($execution_id, 'failed', $e->getMessage(), $duration_ms);
            error_log('U43: Workflow execution failed - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute a node
     *
     * @param array $node Node data
     * @param array $context Execution context
     * @param array $graph Execution graph
     * @param array $all_nodes All nodes
     */
    private function execute_node($node, &$context, $graph, $all_nodes) {
        $node_id = $node['id'];
        $node_type = $node['type'];
        
        // Track execution start time
        $node_start_time = microtime(true);
        
        // Log node execution start
        $this->log_node_start($node_id, $node_type, $context);
        
        try {
            $output = null;
            
            switch ($node_type) {
                case 'trigger':
                    $output = $context['trigger_data'];
                    break;
                    
                case 'agent':
                    // Support both node-level and config-level agent_id
                    $agent_id = $node['agent_id'] ?? $node['config']['agent_id'] ?? '';
                    if (empty($agent_id)) {
                        error_log("U43: Agent node '{$node_id}' is missing agent_id");
                        throw new \Exception('Agent node missing agent_id');
                    }
                    
                    // Get user's prompt from config (stored directly in config.prompt, not config.inputs.prompt)
                    $user_prompt = $node['config']['prompt'] ?? '';
                    
                    // Resolve inputs from config (for action nodes, not for prompts)
                    $resolved_inputs = $this->resolve_inputs($node['config']['inputs'] ?? [], $context);
                    
                    // Validate that a prompt is provided - no default prompts allowed
                    if (empty($user_prompt) && empty($resolved_inputs['prompt'])) {
                        throw new \Exception('Agent node requires a prompt. Please configure a prompt in the node settings.');
                    }
                    
                    // Use user's prompt if provided, otherwise use from inputs
                    if (!empty($user_prompt)) {
                        // Resolve variables in prompt (e.g., {{trigger_data.content}}, {{node_id.field}})
                        $resolved_inputs['prompt'] = $this->resolve_template($user_prompt, $context);
                    } elseif (isset($resolved_inputs['prompt']) && !empty($resolved_inputs['prompt'])) {
                        // Prompt was in inputs config - resolve variables
                        $resolved_inputs['prompt'] = $this->resolve_template($resolved_inputs['prompt'], $context);
                    } else {
                        // This should not happen due to validation above, but just in case
                        throw new \Exception('Agent node requires a prompt. Please configure a prompt in the node settings.');
                    }
                    
                    // Always include trigger_data in context if available
                    // For comment triggers, only send the content
                    if (!empty($context['trigger_data'])) {
                        $trigger_data = $context['trigger_data'];
                        
                        // For comment triggers, only include the content
                        if (isset($trigger_data['comment_id']) || isset($trigger_data['comment'])) {
                            $simplified_context = [];
                            
                            // Only include content if it exists
                            if (!empty($trigger_data['content'])) {
                                $simplified_context['content'] = $trigger_data['content'];
                            }
                            
                            // Merge with existing context if set, otherwise use simplified context
                            if (!isset($resolved_inputs['context'])) {
                                $resolved_inputs['context'] = $simplified_context;
                            } elseif (is_array($resolved_inputs['context'])) {
                                // Merge simplified context with user-provided context
                                $resolved_inputs['context'] = array_merge($simplified_context, $resolved_inputs['context']);
                            } else {
                                $resolved_inputs['context'] = $simplified_context;
                            }
                        } else {
                            // For other trigger types, use trigger_data as-is
                            if (!isset($resolved_inputs['context'])) {
                                $resolved_inputs['context'] = $trigger_data;
                            } elseif (is_array($resolved_inputs['context'])) {
                                $resolved_inputs['context'] = array_merge($resolved_inputs['context'], $trigger_data);
                            } else {
                                $resolved_inputs['context'] = $trigger_data;
                            }
                        }
                    }
                    
                    $inputs = $resolved_inputs;
                    
                    // Extract node-specific config (excluding inputs which are handled separately)
                    $node_config = $node['config'] ?? [];
                    unset($node_config['inputs']); // Remove inputs as they're passed separately
                    
                    $prompt_to_send = $inputs['prompt'] ?? '';
                    
                    // Get decision options for logging and passing to agent
                    $custom_decisions = $node['config']['custom_decisions'] ?? '';
                    $decision_options = ['yes', 'no', 'maybe'];
                    if (!empty($custom_decisions)) {
                        $custom_list = array_map('trim', explode(',', $custom_decisions));
                        $decision_options = array_merge($decision_options, $custom_list);
                        $decision_options = array_unique($decision_options);
                    }
                    
                    // Store inputs for logging (will be added to output_data)
                    // Make sure we capture the actual prompt that will be sent
                    // The agent will construct: $prompt . "\n\nContext: " . json_encode($context, JSON_PRETTY_PRINT)
                    // So we should capture the full message that will be sent
                    $full_user_message = $prompt_to_send;
                    if (!empty($inputs['context'])) {
                        $full_user_message .= "\n\nContext: " . json_encode($inputs['context'], JSON_PRETTY_PRINT);
                    }
                    
                    $agent_inputs_log = [
                        'prompt' => $prompt_to_send, // The prompt that will be sent
                        'context' => $inputs['context'] ?? [],
                        'decision_options' => $decision_options, // Decision options array
                        'full_user_message' => $full_user_message, // The complete message sent to LLM
                        'full_inputs' => $inputs,
                    ];
                    
                    try {
                        // Set execution timeout for agent (60 seconds)
                        $timeout = 60;
                        $start_time = microtime(true);
                        
                        // Map 'prompt' to 'message' for LLM chat agent
                        // LLM chat agent expects 'message' input, but executor uses 'prompt'
                        if ($agent_id === 'llm_chat_agent' && isset($inputs['prompt'])) {
                            $inputs['message'] = $inputs['prompt'];
                            // Keep prompt for backward compatibility, but message takes precedence
                        }
                        
                        // Add decision options to inputs so agent can include them in the message
                        $inputs['decision_options'] = $decision_options;
                        
                        $output = $this->agents_registry->execute($agent_id, $inputs, $node_config);
                        
                        $elapsed = microtime(true) - $start_time;
                        if ($elapsed > $timeout) {
                            throw new \Exception("Agent execution timed out after {$timeout} seconds");
                        }
                        
                        // Add inputs to output for logging
                        if (is_array($output)) {
                            $output['_inputs_sent'] = $agent_inputs_log;
                        } else {
                            $output = [
                                'result' => $output,
                                '_inputs_sent' => $agent_inputs_log,
                            ];
                        }
                    } catch (\Exception $e) {
                        // Store inputs even on error for debugging
                        error_log("U43: Agent '{$agent_id}' execution failed: " . $e->getMessage());
                        error_log("U43: Stack trace: " . $e->getTraceAsString());
                        
                        // Add inputs to error context
                        $error_output = [
                            'error' => $e->getMessage(),
                            '_inputs_sent' => $agent_inputs_log,
                        ];
                        
                        // Store error output before throwing
                        $context[$node_id] = $error_output;
                        $node_duration_ms = (microtime(true) - $node_start_time) * 1000;
                        $this->log_node_error($node_id, $e, $node_duration_ms);
                        
                        // Update the log with inputs
                        global $wpdb;
                        $wpdb->update(
                            $wpdb->prefix . 'u43_node_logs',
                            ['output_data' => json_encode($error_output)],
                            [
                                'execution_id' => $this->execution_id,
                                'node_id' => $node_id,
                            ],
                            ['%s'],
                            ['%d', '%s']
                        );
                        
                        throw $e;
                    }
                    break;
                    
                case 'action':
                    $action_type = $node['config']['action_type'] ?? '';
                    if ($action_type === 'conditional') {
                        $output = $this->execute_conditional_action($node, $context);
                    } else {
                        $tool_id = $node['config']['tool_id'] ?? '';
                        
                        if (empty($tool_id)) {
                            error_log("U43: Action node '{$node_id}' is missing tool_id in config: " . json_encode($node['config']));
                            throw new \Exception("Action node '{$node_id}' is missing tool_id");
                        }
                        
                        $inputs = $this->resolve_inputs($node['config']['inputs'] ?? [], $context);
                        
                        // Auto-populate comment_id from trigger_data if not provided
                        // This handles cases where action nodes don't have inputs configured
                        if (empty($inputs['comment_id']) && !empty($context['trigger_data']['comment_id'])) {
                            // Check if this tool requires comment_id (WordPress comment tools)
                            $comment_tools = [
                                'wordpress_approve_comment',
                                'wordpress_spam_comment',
                                'wordpress_delete_comment',
                                'wordpress_send_email', // May also use comment_id
                            ];
                            
                            if (in_array($tool_id, $comment_tools) || strpos($tool_id, 'wordpress_') === 0) {
                                // Ensure comment_id is an integer
                                $comment_id = absint($context['trigger_data']['comment_id']);
                                    if ($comment_id > 0) {
                                        $inputs['comment_id'] = $comment_id;
                                    }
                            }
                        }
                        
                        // Ensure comment_id is an integer if present
                        if (isset($inputs['comment_id'])) {
                            $inputs['comment_id'] = absint($inputs['comment_id']);
                        }
                        
                        $output = $this->tools_registry->execute($tool_id, $inputs, $context);
                    }
                    break;
                    
                case 'condition':
                    $output = $this->evaluate_condition($node, $context);
                    break;
            }
            
            // Store output in context
            $context[$node_id] = $output;
            
            // Store node type metadata for parent variable resolution
            if (!isset($context['_node_types'])) {
                $context['_node_types'] = [];
            }
            $context['_node_types'][$node_id] = $node_type;
            
            // Calculate node execution duration
            $node_duration_ms = (microtime(true) - $node_start_time) * 1000;
            
            // Log node execution success
            $this->log_node_success($node_id, $output, $node_duration_ms);
            
            // Store button message mapping if this is a button message node
            if ($node_type === 'action' && isset($node['config']['tool_id']) && $node['config']['tool_id'] === 'whatsapp_send_button_message') {
                Button_Message_Service::store_mapping($node_id, $output, $node, $this->workflow_id, $this->execution_id);
            }
            
            // Execute connected nodes
            $next_nodes = $graph[$node_id] ?? [];
            
            // Try to find a handler for this node
            $handler_found = false;
            foreach ($this->node_handlers as $handler) {
                if ($handler->can_handle($node, $output)) {
                    $handler_found = true;
                    $result = $handler->handle(
                        $node,
                        $output,
                        $next_nodes,
                        $all_nodes,
                        $context,
                        function($next_node, $node_context) use (&$context, $graph, $all_nodes) {
                            $this->execute_node($next_node, $node_context, $graph, $all_nodes);
                        },
                        function($nodes, $node_id) {
                            return $this->find_node_by_id($nodes, $node_id);
                        }
                    );
                    
                    // Update context if handler modified it
                    if (isset($result['context'])) {
                        $context = $result['context'];
                    }
                    
                    // Track errors
                    if (!empty($result['has_errors'])) {
                        $this->has_failed_nodes = true;
                        if (empty($this->first_error_message) && !empty($result['error_message'])) {
                            $this->first_error_message = $result['error_message'];
                        }
                    }
                    
                    // Track if execution is paused (waiting for button click)
                    if (!empty($result['paused'])) {
                        $this->is_paused = true;
                    }
                    
                    break;
                }
            }
            
            // If no handler found, use normal execution
            if (!$handler_found) {
                // Normal execution - execute all connected nodes
                foreach ($next_nodes as $edge_data) {
                    $next_node_id = is_array($edge_data) ? $edge_data['node_id'] : $edge_data;
                    $next_node = $this->find_node_by_id($all_nodes, $next_node_id);
                    if ($next_node) {
                        try {
                            $this->execute_node($next_node, $context, $graph, $all_nodes);
                        } catch (\Exception $child_exception) {
                            // Child node failed - mark workflow as failed but continue executing other nodes
                            $this->has_failed_nodes = true;
                            if (empty($this->first_error_message)) {
                                $this->first_error_message = "Node '{$next_node_id}' failed: " . $child_exception->getMessage();
                            }
                            // Don't re-throw - let other nodes execute
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            // Calculate node execution duration even on error
            $node_duration_ms = (microtime(true) - $node_start_time) * 1000;
            // Only log error for THIS node - don't affect previous nodes
            $this->log_node_error($node_id, $e, $node_duration_ms);
            // Re-throw to mark workflow as failed, but individual node statuses are already set
            throw $e;
        }
    }
    
    /**
     * Evaluate condition node
     *
     * @param array $node Node data
     * @param array $context Execution context
     * @return array
     */
    private function evaluate_condition($node, $context) {
        $config = $node['config'] ?? [];
        $field = $config['field'] ?? '';
        $operator = $config['operator'] ?? 'equals';
        $value = $config['value'] ?? '';
        
        // Resolve field value from context
        $field_value = $this->resolve_value($field, $context);
        
        $result = false;
        
        switch ($operator) {
            case 'equals':
                $result = $field_value == $value;
                break;
            case 'not_equals':
                $result = $field_value != $value;
                break;
            case 'contains':
                $result = is_string($field_value) && strpos($field_value, $value) !== false;
                break;
            case 'greater_than':
                $result = is_numeric($field_value) && is_numeric($value) && $field_value > $value;
                break;
            case 'less_than':
                $result = is_numeric($field_value) && is_numeric($value) && $field_value < $value;
                break;
            case 'exists':
                $result = isset($field_value) && $field_value !== null;
                break;
            case 'empty':
                $result = empty($field_value);
                break;
        }
        
        return [
            'result' => $result,
            'field' => $field,
            'field_value' => $field_value,
            'operator' => $operator,
            'compare_value' => $value,
        ];
    }
    
    /**
     * Resolve template string with variables (supports {{variable}}, {{node_id.field}}, {{node_id.array[0].field}})
     *
     * @param string $template Template string
     * @param array $context Execution context
     * @return string Resolved string
     */
    private function resolve_template($template, $context) {
        if (empty($template) || !is_string($template)) {
            return $template;
        }
        
        // Find all {{variable}} patterns
        if (preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches)) {
            $resolved = $template;
            foreach ($matches[1] as $match) {
                $var_path = trim($match);
                $var_value = $this->resolve_variable_path($var_path, $context);
                
                // Convert to string for replacement
                if (is_array($var_value)) {
                    $var_value = json_encode($var_value);
                } elseif (is_object($var_value)) {
                    $var_value = json_encode($var_value);
                } elseif ($var_value === null) {
                    $var_value = '';
                } else {
                    $var_value = (string)$var_value;
                }
                
                $resolved = str_replace('{{' . $match . '}}', $var_value, $resolved);
            }
            return $resolved;
        }
        
        return $template;
    }
    
    /**
     * Resolve a variable path (supports dots and array brackets)
     * Examples: trigger_data.content, node_123.decision, node_123.results[0].status
     * Also supports: parents.agent.response, parents.action.result
     *
     * @param string $path Variable path
     * @param array $context Execution context
     * @return mixed Resolved value or null
     */
    private function resolve_variable_path($path, $context) {
        $remaining_path = trim($path);
        
        // Check for combined parent variable pattern: parents.<type>.<field>
        if (preg_match('/^parents\.([a-zA-Z0-9_]+)\.(.+)$/', $remaining_path, $parent_match)) {
            $parent_type = $parent_match[1];
            $field_path = $parent_match[2];
            
            // Find all nodes of the specified type that have been executed
            $node_types = $context['_node_types'] ?? [];
            $matching_nodes = [];
            
            foreach ($node_types as $node_id => $node_type) {
                if ($node_type === $parent_type && isset($context[$node_id])) {
                    $matching_nodes[] = $node_id;
                }
            }
            
            // If we have matching nodes, try to resolve the field from the first one
            // (In practice, only one parent node will have executed before the current node)
            if (!empty($matching_nodes)) {
                // Try nodes in reverse order (most recently executed first)
                $matching_nodes = array_reverse($matching_nodes);
                
                // First, try to find a node that has the field (for button message nodes, prioritize nodes with the button ID)
                foreach ($matching_nodes as $node_id) {
                    $node_output = $context[$node_id];
                    if (is_array($node_output)) {
                        // Check if this node has the field directly (for button IDs, check if the field exists as a key)
                        if (isset($node_output[$field_path])) {
                            return $node_output[$field_path];
                        }
                        // Otherwise, try to resolve field path within the node output
                        $field_value = $this->resolve_field_path($field_path, $node_output);
                        if ($field_value !== null) {
                            return $field_value;
                        }
                    }
                }
            }
            
            // No matching parent node found
            return null;
        }
        
        // Standard variable path resolution
        $var_value = $context;
        
        // Parse path token by token (handles both dots and brackets)
        while (!empty($remaining_path)) {
            // Check for array bracket pattern: key[index]
            if (preg_match('/^([a-zA-Z0-9_]+)\[(\d+)\](.*)$/', $remaining_path, $array_match)) {
                $key = $array_match[1];
                $index = (int)$array_match[2];
                $remaining_path = ltrim($array_match[3], '.');
                
                // Access array element
                if (is_array($var_value) && isset($var_value[$key]) && is_array($var_value[$key])) {
                    if (isset($var_value[$key][$index])) {
                        $var_value = $var_value[$key][$index];
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            } 
            // Check for simple key access: key or key.field
            elseif (preg_match('/^([a-zA-Z0-9_]+)(.*)$/', $remaining_path, $key_match)) {
                $key = $key_match[1];
                $remaining_path = ltrim($key_match[2], '.');
                
                // Access key
                if (is_array($var_value) && isset($var_value[$key])) {
                    $var_value = $var_value[$key];
                } else {
                    return null;
                }
            } else {
                // Invalid path format
                return null;
            }
        }
        
        return $var_value;
    }
    
    /**
     * Resolve a field path within a data structure (supports dots and array brackets)
     * Helper function for resolving nested fields in parent node outputs
     *
     * @param string $field_path Field path (e.g., "response", "results[0].status")
     * @param mixed $data Data structure to search in
     * @return mixed Resolved value or null
     */
    private function resolve_field_path($field_path, $data) {
        if (!is_array($data)) {
            return null;
        }
        
        $remaining_path = trim($field_path);
        $var_value = $data;
        
        while (!empty($remaining_path)) {
            // Check for array bracket pattern: key[index]
            if (preg_match('/^([a-zA-Z0-9_]+)\[(\d+)\](.*)$/', $remaining_path, $array_match)) {
                $key = $array_match[1];
                $index = (int)$array_match[2];
                $remaining_path = ltrim($array_match[3], '.');
                
                if (is_array($var_value) && isset($var_value[$key]) && is_array($var_value[$key])) {
                    if (isset($var_value[$key][$index])) {
                        $var_value = $var_value[$key][$index];
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            } 
            // Check for simple key access
            elseif (preg_match('/^([a-zA-Z0-9_]+)(.*)$/', $remaining_path, $key_match)) {
                $key = $key_match[1];
                $remaining_path = ltrim($key_match[2], '.');
                
                if (is_array($var_value) && isset($var_value[$key])) {
                    $var_value = $var_value[$key];
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }
        
        return $var_value;
    }
    
    /**
     * Resolve a value from context (supports template syntax)
     *
     * @param string $expression Expression to resolve
     * @param array $context Execution context
     * @return mixed
     */
    private function resolve_value($expression, $context) {
        // Handle template syntax {{variable}} or {{node_id.field}}
        if (preg_match_all('/\{\{([^}]+)\}\}/', $expression, $matches)) {
            $resolved = $expression;
            foreach ($matches[1] as $match) {
                $var_value = $this->resolve_variable_path(trim($match), $context);
                $resolved = str_replace('{{' . $match . '}}', $var_value ?? '', $resolved);
            }
            // If entire expression was a template, return the resolved value
            if (trim($expression) === '{{' . $matches[1][0] . '}}') {
                return $var_value;
            }
            return $resolved;
        }
        
        // Direct field access (legacy support)
        $var_value = $this->resolve_variable_path($expression, $context);
        return $var_value;
    }
    
    /**
     * Execute conditional action
     *
     * @param array $node Node data
     * @param array $context Execution context
     * @return mixed
     */
    private function execute_conditional_action($node, $context) {
        $conditions = $node['config']['conditions'] ?? [];
        
        // Get decision from previous agent node
        $decision = null;
        foreach ($context as $key => $value) {
            if (is_array($value) && isset($value['decision'])) {
                $decision = $value['decision'];
                break;
            }
        }
        
        if (!$decision) {
            return ['success' => false, 'message' => 'No decision found'];
        }
        
        foreach ($conditions as $condition) {
            $condition_decision = str_replace(["decision == '", "'"], '', $condition['if']);
            if ($condition_decision === $decision) {
                $tool_id = $condition['then'];
                $inputs = ['comment_id' => $context['trigger_data']['comment_id']];
                return $this->tools_registry->execute($tool_id, $inputs, $context);
            }
        }
        
        return ['success' => false, 'message' => 'No matching condition'];
    }
    
    /**
     * Resolve input values from context
     *
     * @param array $input_config Input configuration
     * @param array $context Execution context
     * @return array
     */
    private function resolve_inputs($input_config, $context) {
        $resolved = [];
        foreach ($input_config as $key => $value) {
            // Use resolve_template for proper variable resolution (supports combined parent variables)
            if (is_string($value)) {
                $resolved[$key] = $this->resolve_template($value, $context);
            } elseif (is_array($value)) {
                // Recursively resolve arrays
                $resolved[$key] = $this->resolve_inputs($value, $context);
            } else {
                $resolved[$key] = $value;
            }
        }
        return $resolved;
    }
    
    /**
     * Build execution graph from nodes and edges
     *
     * @param array $nodes Nodes
     * @param array $edges Edges
     * @return array
     */
    private function build_graph($nodes, $edges) {
        $graph = [];
        foreach ($edges as $edge) {
            $from = $edge['from'] ?? $edge['source'] ?? null;
            $to = $edge['to'] ?? $edge['target'] ?? null;
            $sourceHandle = $edge['sourceHandle'] ?? null;
            
            if ($from && $to) {
                if (!isset($graph[$from])) {
                    $graph[$from] = [];
                }
                // Store edge data including sourceHandle for condition node routing
                $graph[$from][] = [
                    'node_id' => $to,
                    'sourceHandle' => $sourceHandle,
                ];
            }
        }
        return $graph;
    }
    
    /**
     * Find node by ID
     *
     * @param array $nodes Nodes
     * @param string $node_id Node ID
     * @return array|null
     */
    private function find_node_by_id($nodes, $node_id) {
        foreach ($nodes as $node) {
            if ($node['id'] === $node_id) {
                return $node;
            }
        }
        return null;
    }
    
    /**
     * Find trigger node
     *
     * @param array $nodes Nodes
     * @return array|null
     */
    private function find_trigger_node($nodes) {
        foreach ($nodes as $node) {
            if ($node['type'] === 'trigger') {
                return $node;
            }
        }
        return null;
    }
    
    /**
     * Create execution record
     *
     * @param int $workflow_id Workflow ID
     * @param array $trigger_data Trigger data
     * @return int|false Execution ID or false on failure
     */
    private function create_execution($workflow_id, $trigger_data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'u43_executions',
            [
                'workflow_id' => $workflow_id,
                'status' => 'running',
                'trigger_data' => json_encode($trigger_data),
            ],
            ['%d', '%s', '%s']
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Log node execution start
     *
     * @param string $node_id Node ID
     * @param string $node_type Node type
     * @param array $context Context
     */
    private function log_node_start($node_id, $node_type, $context) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'u43_node_logs',
            [
                'execution_id' => $this->execution_id,
                'node_id' => $node_id,
                'node_type' => $node_type,
                'status' => 'running',
                'input_data' => json_encode($context),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Log node execution success
     *
     * @param string $node_id Node ID
     * @param mixed $output Output data
     * @param float $duration_ms Duration in milliseconds
     */
    private function log_node_success($node_id, $output, $duration_ms = null) {
        global $wpdb;
        
        $update_data = [
            'status' => 'success',
            'completed_at' => current_time('mysql'),
            'output_data' => json_encode($output),
        ];
        
        $format = ['%s', '%s', '%s'];
        
        if ($duration_ms !== null) {
            $update_data['duration_ms'] = round($duration_ms);
            $format[] = '%d';
        }
        
        $wpdb->update(
            $wpdb->prefix . 'u43_node_logs',
            $update_data,
            [
                'execution_id' => $this->execution_id,
                'node_id' => $node_id,
            ],
            $format,
            ['%d', '%s']
        );
    }
    
    /**
     * Log node execution error
     *
     * @param string $node_id Node ID
     * @param \Exception $exception Exception
     * @param float $duration_ms Duration in milliseconds
     */
    private function log_node_error($node_id, $exception, $duration_ms = null) {
        global $wpdb;
        
        $update_data = [
            'status' => 'failed',
            'completed_at' => current_time('mysql'),
            'error_message' => $exception->getMessage(),
            'error_stack' => $exception->getTraceAsString(),
        ];
        
        $format = ['%s', '%s', '%s', '%s'];
        
        if ($duration_ms !== null) {
            $update_data['duration_ms'] = round($duration_ms);
            $format[] = '%d';
        }
        
        $wpdb->update(
            $wpdb->prefix . 'u43_node_logs',
            $update_data,
            [
                'execution_id' => $this->execution_id,
                'node_id' => $node_id,
            ],
            $format,
            ['%d', '%s']
        );
    }
    
    /**
     * Update execution status
     *
     * @param int $execution_id Execution ID
     * @param string $status Status
     * @param string $error_message Error message
     * @param float $duration_ms Duration in milliseconds
     */
    private function update_execution_status($execution_id, $status, $error_message = '', $duration_ms = null) {
        global $wpdb;
        
        $update_data = [
            'status' => $status,
            'completed_at' => current_time('mysql'),
        ];
        
        $format = ['%s', '%s'];
        
        if ($error_message) {
            $update_data['error_message'] = $error_message;
            $format[] = '%s';
        }
        
        if ($duration_ms !== null) {
            $update_data['duration_ms'] = round($duration_ms);
            $format[] = '%d';
        }
        
        $wpdb->update(
            $wpdb->prefix . 'u43_executions',
            $update_data,
            ['id' => $execution_id],
            $format,
            ['%d']
        );
    }
    
    /**
     * Continue workflow execution from a button message node when button is clicked
     * Delegates to WhatsApp Button Continuation Service
     *
     * @param string $message_id WhatsApp message ID
     * @param string $button_id Button ID that was clicked
     * @param array $button_data Button data (button_id, button_title, interactive_type)
     * @return int|false Execution ID or false on failure
     */
    public function continue_from_button_message($message_id, $button_id, $button_data = []) {
        return Button_Continuation_Service::continue_with_executor($this, $message_id, $button_id, $button_data);
    }
    
    /**
     * Continue execution from a specific node (used by continuation services)
     *
     * @param array $node Node to execute
     * @param array $context Execution context
     * @param array $graph Execution graph
     * @param array $all_nodes All nodes
     */
    public function continue_execution_from_node($node, $context, $graph, $all_nodes) {
        $this->execute_node($node, $context, $graph, $all_nodes);
    }
    
    /**
     * Get execution state (for continuation services to check final status)
     *
     * @return array Array with 'has_failed_nodes' and 'first_error_message'
     */
    public function get_execution_state() {
        return [
            'has_failed_nodes' => $this->has_failed_nodes,
            'first_error_message' => $this->first_error_message,
        ];
    }
    
    /**
     * Finalize continuation execution and update status
     *
     * @param int $execution_id Execution ID
     * @param float $start_time Start time (microtime)
     * @return bool True if successful, false if failed
     */
    public function finalize_continuation($execution_id, $start_time) {
        $duration_ms = (microtime(true) - $start_time) * 1000;
        
        if ($this->has_failed_nodes) {
            $this->update_execution_status($execution_id, 'failed', $this->first_error_message, $duration_ms);
            return false;
        }
        
        $this->update_execution_status($execution_id, 'success', '', $duration_ms);
        return true;
    }
}

