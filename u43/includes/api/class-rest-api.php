<?php
/**
 * REST API Class
 *
 * @package U43
 */

namespace U43\API;

class REST_API {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp_ajax_u43_create_workflow', [$this, 'ajax_create_workflow']);
        add_action('wp_ajax_u43_update_workflow', [$this, 'ajax_update_workflow']);
        add_action('wp_ajax_u43_get_node_types', [$this, 'ajax_get_node_types']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('u43/v1', '/workflows', [
            'methods' => 'GET',
            'callback' => [$this, 'get_workflows'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route('u43/v1', '/workflows/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_workflow'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route('u43/v1', '/workflows', [
            'methods' => 'POST',
            'callback' => [$this, 'create_workflow'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route('u43/v1', '/workflows/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_workflow'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route('u43/v1', '/node-types', [
            'methods' => 'GET',
            'callback' => [$this, 'get_node_types'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route('u43/v1', '/executions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_executions'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route('u43/v1', '/executions/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_execution'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        
        register_rest_route('u43/v1', '/executions/(?P<id>\d+)/logs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_execution_logs'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }
    
    /**
     * Check permissions
     *
     * @return bool
     */
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get workflows
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_workflows($request) {
        $flow_manager = U43()->get_flow_manager();
        $workflows = $flow_manager->get_workflows();
        
        return new \WP_REST_Response($workflows, 200);
    }
    
    /**
     * Get workflow
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_workflow($request) {
        $workflow_id = $request->get_param('id');
        $flow_manager = U43()->get_flow_manager();
        $workflow = $flow_manager->get_workflow($workflow_id);
        
        if (!$workflow) {
            return new \WP_Error('not_found', 'Workflow not found', ['status' => 404]);
        }
        
        return new \WP_REST_Response($workflow, 200);
    }
    
    /**
     * Create workflow
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function create_workflow($request) {
        $data = $request->get_json_params();
        $flow_manager = U43()->get_flow_manager();
        
        $workflow_id = $flow_manager->create_workflow([
            'title' => sanitize_text_field($data['title'] ?? 'Untitled Workflow'),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'draft'),
            'nodes' => $data['nodes'] ?? [],
            'edges' => $data['edges'] ?? [],
        ]);
        
        if ($workflow_id) {
            return new \WP_REST_Response(['id' => $workflow_id], 201);
        }
        
        return new \WP_Error('creation_failed', 'Failed to create workflow', ['status' => 500]);
    }
    
    /**
     * Update workflow
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function update_workflow($request) {
        $workflow_id = $request->get_param('id');
        $data = $request->get_json_params();
        $flow_manager = U43()->get_flow_manager();
        
        $result = $flow_manager->update_workflow($workflow_id, [
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? ''),
            'workflow_data' => [
                'nodes' => $data['nodes'] ?? [],
                'edges' => $data['edges'] ?? [],
            ],
        ]);
        
        if ($result) {
            return new \WP_REST_Response(['success' => true], 200);
        }
        
        return new \WP_Error('update_failed', 'Failed to update workflow', ['status' => 500]);
    }
    
    /**
     * Get node types
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_node_types($request) {
        // Get available triggers, agents, and tools from registries
        $triggers_registry = U43()->get_triggers_registry();
        $agents_registry = U43()->get_agents_registry();
        $tools_registry = U43()->get_tools_registry();
        
        return new \WP_REST_Response([
            'triggers' => $triggers_registry->get_all(),
            'agents' => $agents_registry->get_all(),
            'tools' => $tools_registry->get_all(),
        ], 200);
    }
    
    /**
     * AJAX: Create workflow
     */
    public function ajax_create_workflow() {
        check_ajax_referer('u43_workflow_action', 'u43_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $workflow_data = json_decode(stripslashes($_POST['workflow_data'] ?? '{}'), true);
        $flow_manager = U43()->get_flow_manager();
        
        $workflow_id = $flow_manager->create_workflow([
            'title' => sanitize_text_field($_POST['title'] ?? 'Untitled Workflow'),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'draft'),
            'nodes' => $workflow_data['nodes'] ?? [],
            'edges' => $workflow_data['edges'] ?? [],
        ]);
        
        if ($workflow_id) {
            // Get created workflow to return created_at timestamp
            $workflow = $flow_manager->get_workflow($workflow_id);
            wp_send_json_success([
                'id' => $workflow_id,
                'created_at' => $workflow ? $workflow->created_at : current_time('mysql'),
                'updated_at' => $workflow ? $workflow->updated_at : current_time('mysql'),
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to create workflow']);
        }
    }
    
    /**
     * AJAX: Update workflow
     */
    public function ajax_update_workflow() {
        check_ajax_referer('u43_workflow_action', 'u43_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $workflow_id = intval($_POST['workflow_id'] ?? 0);
        $workflow_data = json_decode(stripslashes($_POST['workflow_data'] ?? '{}'), true);
        $flow_manager = U43()->get_flow_manager();
        
        $result = $flow_manager->update_workflow($workflow_id, [
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'workflow_data' => [
                'nodes' => $workflow_data['nodes'] ?? [],
                'edges' => $workflow_data['edges'] ?? [],
            ],
        ]);
        
        if ($result) {
            // Get updated workflow to return updated_at timestamp
            $workflow = $flow_manager->get_workflow($workflow_id);
            wp_send_json_success([
                'id' => $workflow_id,
                'updated_at' => $workflow ? $workflow->updated_at : current_time('mysql'),
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update workflow']);
        }
    }
    
    /**
     * AJAX: Get node types
     */
    public function ajax_get_node_types() {
        check_ajax_referer('u43_workflow_action', 'u43_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $triggers_registry = U43()->get_triggers_registry();
        $agents_registry = U43()->get_agents_registry();
        $tools_registry = U43()->get_tools_registry();
        
        wp_send_json_success([
            'triggers' => $triggers_registry->get_all(),
            'agents' => $agents_registry->get_all(),
            'tools' => $tools_registry->get_all(),
        ]);
    }
    
    /**
     * Get executions
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_executions($request) {
        $flow_manager = U43()->get_flow_manager();
        $workflow_id = $request->get_param('workflow_id');
        $status = $request->get_param('status');
        
        $args = [
            'limit' => $request->get_param('limit') ?: 50,
            'offset' => $request->get_param('offset') ?: 0,
        ];
        
        if ($status) {
            $args['status'] = $status;
        }
        
        $executions = $flow_manager->get_executions($workflow_id, $args);
        
        return new \WP_REST_Response($executions, 200);
    }
    
    /**
     * Get execution by ID
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_execution($request) {
        $execution_id = $request->get_param('id');
        $flow_manager = U43()->get_flow_manager();
        $execution = $flow_manager->get_execution($execution_id);
        
        if (!$execution) {
            return new \WP_Error('not_found', 'Execution not found', ['status' => 404]);
        }
        
        return new \WP_REST_Response($execution, 200);
    }
    
    /**
     * Get execution logs
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_execution_logs($request) {
        $execution_id = $request->get_param('id');
        $flow_manager = U43()->get_flow_manager();
        $logs = $flow_manager->get_node_logs($execution_id);
        
        return new \WP_REST_Response($logs, 200);
    }
}

