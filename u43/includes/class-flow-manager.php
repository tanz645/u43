<?php
/**
 * Flow Manager
 *
 * @package U43
 */

namespace U43;

class Flow_Manager {
    
    /**
     * Create a workflow
     *
     * @param array $data Workflow data
     * @return int|false Workflow ID or false on failure
     */
    public function create_workflow($data) {
        global $wpdb;
        
        $workflow_data = [
            'nodes' => $data['nodes'] ?? [],
            'edges' => $data['edges'] ?? [],
            'settings' => $data['settings'] ?? [],
        ];
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'u43_workflows',
            [
                'title' => $data['title'] ?? 'Untitled Workflow',
                'description' => $data['description'] ?? '',
                'status' => $data['status'] ?? 'draft',
                'workflow_data' => json_encode($workflow_data),
                'created_by' => get_current_user_id(),
            ],
            ['%s', '%s', '%s', '%s', '%d']
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get workflow by ID
     *
     * @param int $workflow_id Workflow ID
     * @return object|null
     */
    public function get_workflow($workflow_id) {
        global $wpdb;
        
        $workflow = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}u43_workflows WHERE id = %d",
            $workflow_id
        ));
        
        if ($workflow) {
            $workflow->workflow_data = json_decode($workflow->workflow_data, true);
        }
        
        return $workflow;
    }
    
    /**
     * Get all workflows
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_workflows($args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => null,
            'limit' => 100,
            'offset' => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = '1=1';
        $values = [];
        
        if ($args['status']) {
            $where .= ' AND status = %s';
            $values[] = $args['status'];
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}u43_workflows WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        $workflows = $wpdb->get_results($query);
        
        foreach ($workflows as $workflow) {
            $workflow->workflow_data = json_decode($workflow->workflow_data, true);
        }
        
        return $workflows;
    }
    
    /**
     * Get workflows by trigger
     *
     * @param string $trigger_id Trigger ID
     * @return array
     */
    public function get_workflows_by_trigger($trigger_id) {
        global $wpdb;
        
        $workflows = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}u43_workflows WHERE status = 'published'"
        );
        
        error_log("U43: Found " . count($workflows) . " published workflow(s). Looking for trigger '{$trigger_id}'");
        
        $matching_workflows = [];
        
        foreach ($workflows as $workflow) {
            $workflow_data = json_decode($workflow->workflow_data, true);
            $nodes = $workflow_data['nodes'] ?? [];
            
            foreach ($nodes as $node) {
                if ($node['type'] === 'trigger') {
                    // Check both node-level and config-level trigger_type for backward compatibility
                    $node_trigger_type = $node['trigger_type'] ?? $node['config']['trigger_type'] ?? '';
                    error_log("U43: Checking workflow ID {$workflow->id} - trigger type: '{$node_trigger_type}' vs '{$trigger_id}'");
                    if ($node_trigger_type === $trigger_id) {
                    $matching_workflows[] = $workflow;
                        error_log("U43: Workflow ID {$workflow->id} matches trigger '{$trigger_id}'");
                    break;
                    }
                }
            }
        }
        
        return $matching_workflows;
    }
    
    /**
     * Execute a workflow
     *
     * @param int $workflow_id Workflow ID
     * @param array $trigger_data Trigger data
     * @return int|false Execution ID or false on failure
     */
    public function execute_workflow($workflow_id, $trigger_data = []) {
        $workflow = $this->get_workflow($workflow_id);
        
        if (!$workflow || $workflow->status !== 'published') {
            return false;
        }
        
        $executor = U43()->get_executor();
        return $executor->execute($workflow, $trigger_data);
    }
    
    /**
     * Update workflow
     *
     * @param int $workflow_id Workflow ID
     * @param array $data Workflow data
     * @return bool
     */
    public function update_workflow($workflow_id, $data) {
        global $wpdb;
        
        $update_data = [];
        $format = [];
        
        if (isset($data['title'])) {
            $update_data['title'] = $data['title'];
            $format[] = '%s';
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = $data['description'];
            $format[] = '%s';
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
            $format[] = '%s';
        }
        
        if (isset($data['workflow_data'])) {
            $update_data['workflow_data'] = json_encode($data['workflow_data']);
            $format[] = '%s';
        }
        
        $update_data['updated_by'] = get_current_user_id();
        $format[] = '%d';
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'u43_workflows',
            $update_data,
            ['id' => $workflow_id],
            $format,
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Delete workflow
     *
     * @param int $workflow_id Workflow ID
     * @return bool
     */
    public function delete_workflow($workflow_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $wpdb->prefix . 'u43_workflows',
            ['id' => $workflow_id],
            ['%d']
        ) !== false;
    }
    
    /**
     * Get executions for a workflow
     *
     * @param int $workflow_id Workflow ID
     * @param array $args Query arguments
     * @return array
     */
    public function get_executions($workflow_id = null, $args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 50,
            'offset' => 0,
            'status' => null,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = '1=1';
        $values = [];
        
        if ($workflow_id) {
            $where .= ' AND workflow_id = %d';
            $values[] = $workflow_id;
        }
        
        if ($args['status']) {
            $where .= ' AND status = %s';
            $values[] = $args['status'];
        }
        
        $query = "SELECT e.*, w.title as workflow_title 
                  FROM {$wpdb->prefix}u43_executions e
                  LEFT JOIN {$wpdb->prefix}u43_workflows w ON e.workflow_id = w.id
                  WHERE {$where} 
                  ORDER BY e.started_at DESC 
                  LIMIT %d OFFSET %d";
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        $executions = $wpdb->get_results($query);
        
        // Decode JSON fields
        foreach ($executions as $execution) {
            if ($execution->trigger_data) {
                $execution->trigger_data = json_decode($execution->trigger_data, true);
            }
            if ($execution->result_data) {
                $execution->result_data = json_decode($execution->result_data, true);
            }
        }
        
        return $executions;
    }
    
    /**
     * Get execution by ID
     *
     * @param int $execution_id Execution ID
     * @return object|null
     */
    public function get_execution($execution_id) {
        global $wpdb;
        
        $execution = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, w.title as workflow_title 
             FROM {$wpdb->prefix}u43_executions e
             LEFT JOIN {$wpdb->prefix}u43_workflows w ON e.workflow_id = w.id
             WHERE e.id = %d",
            $execution_id
        ));
        
        if ($execution) {
            if ($execution->trigger_data) {
                $execution->trigger_data = json_decode($execution->trigger_data, true);
            }
            if ($execution->result_data) {
                $execution->result_data = json_decode($execution->result_data, true);
            }
        }
        
        return $execution;
    }
    
    /**
     * Get node logs for an execution
     *
     * @param int $execution_id Execution ID
     * @return array
     */
    public function get_node_logs($execution_id) {
        global $wpdb;
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}u43_node_logs 
             WHERE execution_id = %d 
             ORDER BY started_at ASC",
            $execution_id
        ));
        
        // Decode JSON fields
        foreach ($logs as $log) {
            if ($log->input_data) {
                $log->input_data = json_decode($log->input_data, true);
            }
            if ($log->output_data) {
                $log->output_data = json_decode($log->output_data, true);
            }
        }
        
        return $logs;
    }
}

