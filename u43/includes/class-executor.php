<?php
/**
 * Workflow Executor
 *
 * @package U43
 */

namespace U43;

class Executor {
    
    private $tools_registry;
    private $agents_registry;
    private $execution_id;
    private $has_failed_nodes = false;
    private $first_error_message = '';
    
    /**
     * Constructor
     *
     * @param Registry\Tools_Registry $tools_registry
     * @param Registry\Agents_Registry $agents_registry
     */
    public function __construct($tools_registry, $agents_registry) {
        $this->tools_registry = $tools_registry;
        $this->agents_registry = $agents_registry;
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
                    
                    // Use user's prompt if provided, otherwise use from inputs or auto-generate
                    if (!empty($user_prompt)) {
                        // Use prompt as-is (no template resolution)
                        $resolved_inputs['prompt'] = $user_prompt;
                    } elseif (isset($resolved_inputs['prompt'])) {
                        // Prompt was in inputs config
                    } else {
                        // Auto-populate inputs if not configured
                        // For comment moderation workflows, automatically include comment data
                        if (!empty($context['trigger_data'])) {
                            $trigger_data = $context['trigger_data'];
                            
                            // Get custom decision options from config
                            $custom_decisions = $node['config']['custom_decisions'] ?? '';
                            $decision_options = ['yes', 'no', 'maybe'];
                            
                            if (!empty($custom_decisions)) {
                                // Parse custom decisions (comma-separated)
                                $custom_list = array_map('trim', explode(',', $custom_decisions));
                                $decision_options = array_merge($decision_options, $custom_list);
                                $decision_options = array_unique($decision_options);
                            }
                            
                            $decision_options_str = implode(', ', $decision_options);
                            
                            // Build a default prompt with comment data
                            $comment_content = $trigger_data['content'] ?? '';
                            $comment_author = $trigger_data['author'] ?? 'Unknown';
                            $comment_email = $trigger_data['email'] ?? '';
                            
                            $resolved_inputs['prompt'] = "Review the following comment and make a decision:\n\n";
                            $resolved_inputs['prompt'] .= "Author: {$comment_author}\n";
                            $resolved_inputs['prompt'] .= "Email: {$comment_email}\n";
                            $resolved_inputs['prompt'] .= "Content: {$comment_content}\n\n";
                            $resolved_inputs['prompt'] .= "Make a decision: {$decision_options_str}.";
                        } else {
                            // No trigger data, create minimal prompt
                            $custom_decisions = $node['config']['custom_decisions'] ?? '';
                            $decision_options = ['yes', 'no', 'maybe'];
                            
                            if (!empty($custom_decisions)) {
                                $custom_list = array_map('trim', explode(',', $custom_decisions));
                                $decision_options = array_merge($decision_options, $custom_list);
                                $decision_options = array_unique($decision_options);
                            }
                            
                            $decision_options_str = implode(', ', $decision_options);
                            $resolved_inputs['prompt'] = "Analyze the given information and make a decision: {$decision_options_str}.";
                        }
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
                        
                        $output = $this->tools_registry->execute($tool_id, $inputs);
                    }
                    break;
                    
                case 'condition':
                    $output = $this->evaluate_condition($node, $context);
                    break;
            }
            
            // Store output in context
            $context[$node_id] = $output;
            
            // Calculate node execution duration
            $node_duration_ms = (microtime(true) - $node_start_time) * 1000;
            
            // Log node execution success
            $this->log_node_success($node_id, $output, $node_duration_ms);
            
            // Execute connected nodes
            $next_nodes = $graph[$node_id] ?? [];
            
            // Handle condition node branching
            if ($node_type === 'condition' && is_array($output) && isset($output['result'])) {
                $condition_result = $output['result'];
                
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
            } else {
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
                $parts = explode('.', trim($match));
                $var_value = $context;
                foreach ($parts as $part) {
                    if (is_array($var_value) && isset($var_value[$part])) {
                        $var_value = $var_value[$part];
                    } else {
                        $var_value = null;
                        break;
                    }
                }
                $resolved = str_replace('{{' . $match . '}}', $var_value ?? '', $resolved);
            }
            // If entire expression was a template, return the resolved value
            if (trim($expression) === '{{' . $matches[1][0] . '}}') {
                return $var_value;
            }
            return $resolved;
        }
        
        // Direct field access
        $parts = explode('.', $expression);
        $var_value = $context;
        foreach ($parts as $part) {
            if (is_array($var_value) && isset($var_value[$part])) {
                $var_value = $var_value[$part];
            } else {
                return null;
            }
        }
        
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
                return $this->tools_registry->execute($tool_id, $inputs);
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
            // Simple template resolution: {{variable}} or {{node_id.field}}
            if (is_string($value) && preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches)) {
                $resolved_value = $value;
                foreach ($matches[1] as $match) {
                    $parts = explode('.', trim($match));
                    $var_value = $context;
                    foreach ($parts as $part) {
                        if (is_array($var_value) && isset($var_value[$part])) {
                            $var_value = $var_value[$part];
                        } else {
                            $var_value = null;
                            break;
                        }
                    }
                    $resolved_value = str_replace('{{' . $match . '}}', $var_value ?? '', $resolved_value);
                }
                $resolved[$key] = $resolved_value;
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
}

