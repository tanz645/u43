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
        
        // Ensure REST API is enabled (WordPress.org REST API requirement)
        add_filter('rest_enabled', '__return_true', 999);
        add_filter('rest_jsonp_enabled', '__return_true', 999);
        
        // Enable CORS headers
        add_filter('rest_pre_serve_request', [$this, 'add_cors_headers'], 0, 4);
        
        // Handle OPTIONS preflight requests early
        add_action('init', [$this, 'handle_options_request'], 0);
        
        // Handle webhook very early - before WordPress authentication checks
        // Using parse_request which runs very early in WordPress load
        add_action('parse_request', [$this, 'handle_webhook_early'], 1);
        
        // Also try template_redirect as backup
        add_action('template_redirect', [$this, 'handle_webhook_early'], 1);
        
        // Allow public access to WhatsApp webhook endpoint (bypass authentication)
        // This must run early to override any security plugins
        add_filter('rest_authentication_errors', [$this, 'allow_public_webhook_access'], 999, 1);
        
        // Intercept REST API dispatch before authentication
        add_filter('rest_pre_dispatch', [$this, 'handle_webhook_rest_pre_dispatch'], 10, 3);
        
        // Filter to return plain text for webhook verification (bypass JSON wrapper)
        add_filter('rest_pre_serve_request', [$this, 'serve_webhook_verification'], 10, 4);
    }
    
    /**
     * Set CORS headers
     *
     * @param string|null $origin Origin header value (optional)
     */
    private function set_cors_headers($origin = null) {
        if ($origin === null) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        }
        
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
    
    /**
     * Add CORS headers to REST API responses
     *
     * @param bool $served Whether the request has been served
     * @param \WP_REST_Response|\WP_Error $result Result to serve
     * @param \WP_REST_Request $request Request object
     * @param \WP_REST_Server $server Server instance
     * @return bool
     */
    public function add_cors_headers($served, $result, $request, $server) {
        // Get the origin from the request
        $origin = $request->get_header('Origin');
        
        // If no origin header, allow all origins (for same-origin requests)
        if (empty($origin)) {
            $origin = '*';
        }
        
        $this->set_cors_headers($origin);
        
        return $served;
    }
    
    /**
     * Handle OPTIONS preflight requests
     */
    public function handle_options_request() {
        // Check if this is an OPTIONS request to our API
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            
            // Only handle OPTIONS for our API endpoints
            if (strpos($request_uri, '/wp-json/u43/') !== false) {
                $this->set_cors_headers();
                header('Content-Length: 0');
                status_header(200);
                exit;
            }
        }
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
        
        register_rest_route('u43/v1', '/tools/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tool_config'],
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
        
        // WhatsApp webhook endpoint (public, but verified)
        // According to WordPress.org REST API docs, permission_callback is required
        // Using '__return_true' makes it publicly accessible
        register_rest_route('u43/v1', '/webhooks/whatsapp', [
            'methods' => \WP_REST_Server::ALLMETHODS, // Allow GET, POST, OPTIONS
            'callback' => [$this, 'handle_whatsapp_webhook'],
            'permission_callback' => '__return_true', // Public endpoint, but we verify it
            'args' => [],
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
        
        // Get tool configs for frontend
        $tool_configs = [];
        $tools_dir = U43_PLUGIN_DIR . 'configs/tools/';
        if (is_dir($tools_dir)) {
            $tool_files = glob($tools_dir . '*.json');
            foreach ($tool_files as $tool_file) {
                $file_name = basename($tool_file, '.json');
                $config_content = file_get_contents($tool_file);
                if ($config_content !== false) {
                    $config = json_decode($config_content, true);
                    if ($config && json_last_error() === JSON_ERROR_NONE) {
                        // Use the tool ID from config if available, otherwise use filename
                        // Normalize to use underscores for consistency
                        $tool_id = isset($config['id']) ? $config['id'] : str_replace('-', '_', $file_name);
                        $tool_configs[$tool_id] = $config;
                        // Also store with filename key for backward compatibility
                        if ($file_name !== $tool_id) {
                            $tool_configs[$file_name] = $config;
                        }
                    }
                }
            }
        }
        
        // Get trigger configs for frontend
        $trigger_configs = [];
        $triggers_dir = U43_PLUGIN_DIR . 'configs/triggers/';
        if (is_dir($triggers_dir)) {
            $trigger_files = glob($triggers_dir . '*.json');
            foreach ($trigger_files as $trigger_file) {
                $file_name = basename($trigger_file, '.json');
                $config_content = file_get_contents($trigger_file);
                if ($config_content !== false) {
                    $config = json_decode($config_content, true);
                    if ($config && json_last_error() === JSON_ERROR_NONE) {
                        $trigger_id = isset($config['id']) ? $config['id'] : str_replace('-', '_', $file_name);
                        $trigger_configs[$trigger_id] = $config;
                        // Also store with filename key for backward compatibility
                        if ($file_name !== $trigger_id) {
                            $trigger_configs[$file_name] = $config;
                        }
                    }
                }
            }
        }
        
        // Get agent configs for frontend
        $agent_configs = [];
        $agents_dir = U43_PLUGIN_DIR . 'configs/agents/';
        if (is_dir($agents_dir)) {
            $agent_files = glob($agents_dir . '*.json');
            foreach ($agent_files as $agent_file) {
                $file_name = basename($agent_file, '.json');
                $config_content = file_get_contents($agent_file);
                if ($config_content !== false) {
                    $config = json_decode($config_content, true);
                    if ($config && json_last_error() === JSON_ERROR_NONE) {
                        $agent_id = isset($config['id']) ? $config['id'] : str_replace('-', '_', $file_name);
                        $agent_configs[$agent_id] = $config;
                        // Also store with filename key for backward compatibility
                        if ($file_name !== $agent_id) {
                            $agent_configs[$file_name] = $config;
                        }
                    }
                }
            }
        }
        
        return new \WP_REST_Response([
            'triggers' => $triggers_registry->get_all(),
            'agents' => $agents_registry->get_all(),
            'tools' => $tools_registry->get_all(),
            'tool_configs' => $tool_configs, // Include full tool configs
            'trigger_configs' => $trigger_configs, // Include full trigger configs
            'agent_configs' => $agent_configs, // Include full agent configs
        ], 200);
    }
    
    /**
     * Get tool configuration
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_tool_config($request) {
        $tool_id = $request->get_param('id');
        
        if (empty($tool_id)) {
            return new \WP_Error('invalid_request', 'Tool ID is required', ['status' => 400]);
        }
        
        // Load config directly from JSON file (don't require tool to be registered)
        $config_file = U43_PLUGIN_DIR . 'configs/tools/' . sanitize_file_name($tool_id) . '.json';
        
        if (!file_exists($config_file)) {
            error_log('U43: Tool config file not found: ' . $config_file);
            return new \WP_Error('config_not_found', 'Tool configuration file not found: ' . $tool_id, ['status' => 404]);
        }
        
        $config_content = file_get_contents($config_file);
        if ($config_content === false) {
            error_log('U43: Could not read tool config file: ' . $config_file);
            return new \WP_Error('read_error', 'Could not read tool configuration file', ['status' => 500]);
        }
        
        $config = json_decode($config_content, true);
        
        if (!$config || json_last_error() !== JSON_ERROR_NONE) {
            error_log('U43: Invalid JSON in tool config file: ' . $config_file . ' - Error: ' . json_last_error_msg());
            return new \WP_Error('invalid_config', 'Invalid tool configuration JSON: ' . json_last_error_msg(), ['status' => 500]);
        }
        
        return new \WP_REST_Response($config, 200);
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
    
    /**
     * Handle webhook early - before WordPress authentication checks
     * This catches the request before REST API authentication
     */
    public function handle_webhook_early() {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }
        
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if this is our WhatsApp webhook endpoint
        if (strpos($request_uri, '/wp-json/u43/v1/webhooks/whatsapp') === false) {
            return;
        }
        
        // Log everything for debugging
        $hook_name = current_filter();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        error_log('U43 WhatsApp Webhook: Early handler called - Hook: ' . $hook_name . ', URI: ' . $request_uri . ', Method: ' . $method);
        error_log('U43 WhatsApp Webhook: $_GET = ' . print_r($_GET, true));
        error_log('U43 WhatsApp Webhook: $_SERVER[REQUEST_METHOD] = ' . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET'));
        
        // Handle GET requests (verification)
        if ($method === 'GET') {
            // WordPress converts dots in query parameters to underscores for security
            // So hub.mode becomes hub_mode, hub.verify_token becomes hub_verify_token, etc.
            // Check both formats to be safe
            $mode = '';
            $token = '';
            $challenge = '';
            
            // Try with underscores first (WordPress default)
            if (isset($_GET['hub_mode'])) {
                $mode = sanitize_text_field($_GET['hub_mode']);
            } elseif (isset($_GET['hub.mode'])) {
                $mode = sanitize_text_field($_GET['hub.mode']);
            }
            
            if (isset($_GET['hub_verify_token'])) {
                $token = sanitize_text_field($_GET['hub_verify_token']);
            } elseif (isset($_GET['hub.verify_token'])) {
                $token = sanitize_text_field($_GET['hub.verify_token']);
            }
            
            if (isset($_GET['hub_challenge'])) {
                $challenge = sanitize_text_field($_GET['hub_challenge']);
            } elseif (isset($_GET['hub.challenge'])) {
                $challenge = sanitize_text_field($_GET['hub.challenge']);
            }
            
            // If still empty, try parsing query string manually (preserves dots)
            if (empty($mode) && !empty($request_uri)) {
                $query_string = parse_url($request_uri, PHP_URL_QUERY);
                if ($query_string) {
                    parse_str($query_string, $params);
                    // Check both formats in parsed params
                    $mode = isset($params['hub.mode']) ? sanitize_text_field($params['hub.mode']) : (isset($params['hub_mode']) ? sanitize_text_field($params['hub_mode']) : '');
                    $token = isset($params['hub.verify_token']) ? sanitize_text_field($params['hub.verify_token']) : (isset($params['hub_verify_token']) ? sanitize_text_field($params['hub_verify_token']) : '');
                    $challenge = isset($params['hub.challenge']) ? sanitize_text_field($params['hub.challenge']) : (isset($params['hub_challenge']) ? sanitize_text_field($params['hub_challenge']) : '');
                }
            }
            
            error_log('U43 WhatsApp Webhook Early: Mode=' . $mode . ', Token=' . (!empty($token) ? 'Yes (' . substr($token, 0, 10) . '...)' : 'No') . ', Challenge=' . (!empty($challenge) ? 'Yes' : 'No'));
            
            // Facebook pattern: if (mode === 'subscribe' && token === verifyToken) { res.status(200).send(challenge); }
            if ($mode === 'subscribe' && !empty($challenge)) {
                $verify_token = get_option('u43_whatsapp_webhook_verify_token', '');
                
                error_log('U43 WhatsApp Webhook Early: Stored verify token = ' . (!empty($verify_token) ? substr($verify_token, 0, 10) . '...' : 'NOT SET'));
                
                if (empty($verify_token)) {
                    error_log('U43 WhatsApp Webhook Early: No verify token configured - returning 403');
                    $this->set_cors_headers();
                    status_header(403);
                    exit;
                }
                
                if ($token === $verify_token) {
                    error_log('U43 WhatsApp Webhook Early: WEBHOOK VERIFIED - Token matches, serving plain text challenge: ' . $challenge);
                    // Match Facebook pattern exactly: res.status(200).send(challenge)
                    $this->set_cors_headers();
                    status_header(200);
                    nocache_headers();
                    header('Content-Type: text/plain; charset=UTF-8');
                    // Send challenge as plain text - this is what Facebook expects
                    echo $challenge;
                    exit;
                } else {
                    error_log('U43 WhatsApp Webhook Early: Token mismatch');
                    error_log('U43 WhatsApp Webhook Early: Provided token: ' . ($token ?? 'null'));
                    error_log('U43 WhatsApp Webhook Early: Expected token: ' . substr($verify_token, 0, 20) . '...');
                    error_log('U43 WhatsApp Webhook Early: Token length - Provided: ' . strlen($token ?? '') . ', Expected: ' . strlen($verify_token));
                    $this->set_cors_headers();
                    status_header(403);
                    exit;
                }
            } else {
                error_log('U43 WhatsApp Webhook Early: Invalid request - Mode: ' . $mode . ', Challenge: ' . (!empty($challenge) ? 'Yes' : 'No'));
                $this->set_cors_headers();
                status_header(403);
                exit;
            }
        }
    }
    
    /**
     * Handle webhook via REST API pre-dispatch filter
     * This intercepts the request before authentication
     *
     * @param mixed $result
     * @param \WP_REST_Server $server
     * @param \WP_REST_Request $request
     * @return mixed
     */
    public function handle_webhook_rest_pre_dispatch($result, $server, $request) {
        $route = $request->get_route();
        
        // Check if this is our WhatsApp webhook endpoint
        if ($route !== '/u43/v1/webhooks/whatsapp') {
            return $result;
        }
        
        error_log('U43 WhatsApp Webhook: rest_pre_dispatch called for route: ' . $route);
        
        // Handle GET requests (verification)
        if ($request->get_method() === 'GET') {
            // WordPress converts dots to underscores in query params, so check both formats
            $mode = $request->get_param('hub.mode') ?: $request->get_param('hub_mode') ?: '';
            $token = $request->get_param('hub.verify_token') ?: $request->get_param('hub_verify_token') ?: '';
            $challenge = $request->get_param('hub.challenge') ?: $request->get_param('hub_challenge') ?: '';
            
            error_log('U43 WhatsApp Webhook rest_pre_dispatch: Mode=' . ($mode ?? 'null') . ', Token=' . (!empty($token) ? 'Yes' : 'No') . ', Challenge=' . (!empty($challenge) ? 'Yes' : 'No'));
            
            // Facebook pattern: if (mode === 'subscribe' && token === verifyToken) { res.status(200).send(challenge); }
            if ($mode === 'subscribe' && !empty($challenge)) {
                $verify_token = get_option('u43_whatsapp_webhook_verify_token', '');
                
                if (empty($verify_token)) {
                    error_log('U43 WhatsApp Webhook rest_pre_dispatch: No verify token configured');
                    return new \WP_Error('verification_failed', 'Webhook verify token not configured', ['status' => 403]);
                }
                
                if ($token === $verify_token) {
                    error_log('U43 WhatsApp Webhook rest_pre_dispatch: WEBHOOK VERIFIED - Returning challenge');
                    // Return response that will be served as plain text
                    $response = new \WP_REST_Response($challenge, 200);
                    $response->header('Content-Type', 'text/plain');
                    return $response;
                } else {
                    error_log('U43 WhatsApp Webhook rest_pre_dispatch: Token mismatch');
                    return new \WP_Error('verification_failed', 'Invalid verify token', ['status' => 403]);
                }
            }
            
            return new \WP_Error('verification_failed', 'Invalid verification request', ['status' => 403]);
        }
        
        return $result;
    }
    
    /**
     * Allow public access to WhatsApp webhook endpoint
     * This bypasses WordPress authentication for the webhook route
     *
     * @param \WP_Error|null $result Authentication error, if any
     * @return \WP_Error|null
     */
    public function allow_public_webhook_access($result) {
        // Check if this is a request to our WhatsApp webhook endpoint
        if (!empty($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
            if (strpos($request_uri, '/wp-json/u43/v1/webhooks/whatsapp') !== false) {
                // Allow public access - return null (no error) to bypass authentication
                error_log('U43 WhatsApp Webhook: Allowing public access to webhook endpoint via rest_authentication_errors filter');
                return null;
            }
        }
        
        return $result;
    }
    
    /**
     * Serve webhook verification as plain text (bypass JSON wrapper)
     * This matches Facebook's requirement: res.status(200).send(challenge)
     * Pattern: if (mode === 'subscribe' && token === verifyToken) { res.status(200).send(challenge); }
     *
     * @param bool $served Whether the request has been served
     * @param \WP_REST_Response|\WP_Error $result Result to serve
     * @param \WP_REST_Request $request Request object
     * @param \WP_REST_Server $server Server instance
     * @return bool
     */
    public function serve_webhook_verification($served, $result, $request, $server) {
        // Get the route - WordPress REST API routes might have different formats
        $route = $request->get_route();
        $method = $request->get_method();
        
        // Log for debugging - check if this filter is even being called
        error_log('U43 WhatsApp Webhook Filter: Called - Route=' . $route . ', Method=' . $method);
        
        // Check if this is our WhatsApp webhook endpoint (handle different route formats)
        $is_whatsapp_webhook = (
            $route === '/u43/v1/webhooks/whatsapp' ||
            strpos($route, '/u43/v1/webhooks/whatsapp') === 0 ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/u43/v1/webhooks/whatsapp') !== false)
        );
        
        // Only handle WhatsApp webhook GET requests for verification
        if (!$is_whatsapp_webhook || $method !== 'GET') {
            return $served;
        }
        
        // WordPress converts dots to underscores in query params, so check both formats
        $mode = $request->get_param('hub.mode') ?: $request->get_param('hub_mode') ?: '';
        $token = $request->get_param('hub.verify_token') ?: $request->get_param('hub_verify_token') ?: '';
        $challenge = $request->get_param('hub.challenge') ?: $request->get_param('hub_challenge') ?: '';
        
        // Log for debugging
        error_log('U43 WhatsApp Webhook Filter: Processing verification - Mode=' . ($mode ?? 'null') . ', Token=' . (!empty($token) ? 'Yes' : 'No') . ', Challenge=' . (!empty($challenge) ? 'Yes' : 'No'));
        
        // Facebook pattern: if (mode === 'subscribe' && token === verifyToken) { res.status(200).send(challenge); }
        if ($mode === 'subscribe' && !empty($challenge)) {
            $verify_token = get_option('u43_whatsapp_webhook_verify_token', '');
            
            if (empty($verify_token)) {
                error_log('U43 WhatsApp Webhook Filter: No verify token configured - returning 403');
                $this->set_cors_headers();
                status_header(403);
                exit;
            }
            
            // Exact match: token === verifyToken
            if ($token === $verify_token) {
                error_log('U43 WhatsApp Webhook Filter: WEBHOOK VERIFIED - Token matches, serving plain text challenge: ' . $challenge);
                // Match Facebook pattern exactly: res.status(200).send(challenge)
                $this->set_cors_headers();
                status_header(200);
                nocache_headers();
                header('Content-Type: text/plain; charset=UTF-8');
                // Send challenge as plain text - this is what Facebook expects
                echo $challenge;
                exit; // Return true to indicate we've served the request
            } else {
                error_log('U43 WhatsApp Webhook Filter: Token mismatch - Provided: ' . ($token ?? 'null') . ', Expected: ' . substr($verify_token, 0, 20) . '...');
                // Facebook pattern: else { res.status(403).end(); }
                $this->set_cors_headers();
                status_header(403);
                exit;
            }
        }
        
        // If not a subscribe request or missing challenge, return 403
        if ($mode !== 'subscribe' || empty($challenge)) {
            error_log('U43 WhatsApp Webhook Filter: Invalid request - Mode: ' . ($mode ?? 'null') . ', Challenge: ' . (!empty($challenge) ? 'Yes' : 'No'));
            $this->set_cors_headers();
            status_header(403);
            exit;
        }
        
        return $served;
    }
    
    /**
     * Handle WhatsApp webhook
     * GET requests are handled by serve_webhook_verification filter (returns plain text challenge)
     * POST requests are handled here (processes webhook events)
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_whatsapp_webhook($request) {
        // Handle GET requests (webhook verification) - filter should catch it, but handle here as fallback
        if ($request->get_method() === 'GET') {
            // WordPress converts dots to underscores in query params, so check both formats
            $mode = $request->get_param('hub.mode') ?: $request->get_param('hub_mode') ?: '';
            $token = $request->get_param('hub.verify_token') ?: $request->get_param('hub_verify_token') ?: '';
            $challenge = $request->get_param('hub.challenge') ?: $request->get_param('hub_challenge') ?: '';
            
            error_log('U43 WhatsApp Webhook Handler: GET request - Mode=' . ($mode ?? 'null') . ', Token=' . (!empty($token) ? 'Yes' : 'No') . ', Challenge=' . (!empty($challenge) ? 'Yes' : 'No'));
            
            // Facebook pattern: if (mode === 'subscribe' && token === verifyToken) { res.status(200).send(challenge); }
            if ($mode === 'subscribe' && !empty($challenge)) {
                $verify_token = get_option('u43_whatsapp_webhook_verify_token', '');
                
                if (empty($verify_token)) {
                    error_log('U43 WhatsApp Webhook Handler: No verify token configured');
                    return new \WP_Error('verification_failed', 'Webhook verify token not configured', ['status' => 403]);
                }
                
                if ($token === $verify_token) {
                    error_log('U43 WhatsApp Webhook Handler: WEBHOOK VERIFIED - Returning challenge');
                    // Return plain text challenge - use filter to serve as plain text
                    $response = new \WP_REST_Response($challenge, 200);
                    $response->header('Content-Type', 'text/plain');
                    return $response;
                } else {
                    error_log('U43 WhatsApp Webhook Handler: Token mismatch - Provided: ' . ($token ?? 'null') . ', Expected: ' . substr($verify_token, 0, 20) . '...');
                    return new \WP_Error('verification_failed', 'Invalid verify token', ['status' => 403]);
                }
            }
            
            return new \WP_Error('verification_failed', 'Invalid verification request', ['status' => 403]);
        }
        
        // Handle webhook events (POST request)
        // Facebook pattern: app.post('/', (req, res) => { ... res.status(200).end(); })
        $timestamp = current_time('mysql');
        error_log('U43 WhatsApp Webhook: POST request received at ' . $timestamp);
        
        $body = $request->get_json_params();
        
        // Log the webhook payload for debugging
        if (!empty($body)) {
            error_log('U43 WhatsApp Webhook: Body received - ' . json_encode($body, JSON_PRETTY_PRINT));
        } else {
            error_log('U43 WhatsApp Webhook: Empty body received');
        }
        
        // Verify webhook signature (if configured)
        $signature = $request->get_header('X-Hub-Signature-256');
        if (!empty($signature)) {
            $app_secret = get_option('u43_whatsapp_app_secret', '');
            if (!empty($app_secret)) {
                $payload = $request->get_body();
                $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $app_secret);
                
                if (!hash_equals($expected_signature, $signature)) {
                    error_log('U43 WhatsApp Webhook: Invalid signature');
                    return new \WP_Error('invalid_signature', 'Invalid signature', ['status' => 403]);
                }
            }
        }
        
        // Process webhook events
        if (!empty($body)) {
            $this->process_whatsapp_webhook($body);
        }
        
        // Facebook pattern: res.status(200).end() - return empty 200 response
        return new \WP_REST_Response(null, 200);
    }
    
    /**
     * Process WhatsApp webhook events
     *
     * @param array $body Webhook body
     */
    private function process_whatsapp_webhook($body) {
        if (!isset($body['object']) || $body['object'] !== 'whatsapp_business_account') {
            return;
        }
        
        if (!isset($body['entry'])) {
            return;
        }
        
        foreach ($body['entry'] as $entry) {
            if (!isset($entry['changes'])) {
                continue;
            }
            
            foreach ($entry['changes'] as $change) {
                $value = $change['value'] ?? [];
                $field = $change['field'] ?? '';
                
                // Handle messages
                if ($field === 'messages' && isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->trigger_message_received($message, $value);
                    }
                }
                
                // Handle message statuses
                if ($field === 'messages' && isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->trigger_message_status($status);
                    }
                }
                
                // Handle contacts
                if (isset($value['contacts'])) {
                    foreach ($value['contacts'] as $contact) {
                        // Handle contact events if needed
                    }
                }
            }
        }
    }
    
    /**
     * Trigger message received event
     *
     * @param array $message Message data
     * @param array $value Webhook value
     */
    private function trigger_message_received($message, $value) {
        $triggers_registry = U43()->get_triggers_registry();
        
        // Extract contact information (name) from contacts array
        $from_name = '';
        $from_phone = $message['from'] ?? '';
        
        if (isset($value['contacts']) && is_array($value['contacts'])) {
            foreach ($value['contacts'] as $contact) {
                // Match contact by wa_id (WhatsApp ID)
                if (isset($contact['wa_id']) && $contact['wa_id'] === $from_phone) {
                    $from_name = $contact['profile']['name'] ?? '';
                    break;
                }
            }
        }
        
        $data = [
            'message_id' => $message['id'] ?? '',
            'from' => $from_phone,
            'from_name' => $from_name,
            'to' => $value['metadata']['display_phone_number'] ?? '',
            'phone_number_id' => $value['metadata']['phone_number_id'] ?? '',
            'message_text' => $message['text']['body'] ?? '',
            'message_type' => $message['type'] ?? 'text',
            'timestamp' => isset($message['timestamp']) ? (int)$message['timestamp'] : time(),
        ];
        
        // Handle interactive messages (button clicks)
        if (isset($message['interactive']) && $message['interactive']['type'] === 'button_reply') {
            $interactive = $message['interactive'];
            $data['message_type'] = 'interactive';
            $data['interactive_type'] = 'button_reply';
            $data['button_id'] = $interactive['button_reply']['id'] ?? '';
            $data['button_title'] = $interactive['button_reply']['title'] ?? '';
            $data['message_text'] = $data['button_title']; // Use button title as message text
        }
        
        // Handle media messages
        if (isset($message['image'])) {
            $data['media_url'] = $message['image']['id'] ?? '';
            $data['media_type'] = 'image';
        } elseif (isset($message['video'])) {
            $data['media_url'] = $message['video']['id'] ?? '';
            $data['media_type'] = 'video';
        } elseif (isset($message['document'])) {
            $data['media_url'] = $message['document']['id'] ?? '';
            $data['media_type'] = 'document';
        } elseif (isset($message['audio'])) {
            $data['media_url'] = $message['audio']['id'] ?? '';
            $data['media_type'] = 'audio';
        }
        
        error_log('U43 WhatsApp Webhook: Triggering whatsapp_message_received with data: ' . json_encode($data));
        $triggers_registry->trigger('whatsapp_message_received', $data);
    }
    
    /**
     * Trigger message status event
     *
     * @param array $status Status data
     */
    private function trigger_message_status($status) {
        $triggers_registry = U43()->get_triggers_registry();
        $status_type = $status['status'] ?? '';
        
        $data = [
            'message_id' => $status['id'] ?? '',
            'to' => $status['recipient_id'] ?? '',
            'timestamp' => $status['timestamp'] ?? time(),
        ];
        
        switch ($status_type) {
            case 'sent':
                $triggers_registry->trigger('whatsapp_message_sent', array_merge($data, [
                    'status' => 'sent'
                ]));
                break;
            case 'delivered':
                $triggers_registry->trigger('whatsapp_message_delivered', array_merge($data, [
                    'delivered_at' => $status['timestamp'] ?? time()
                ]));
                break;
            case 'read':
                $triggers_registry->trigger('whatsapp_message_read', [
                    'message_id' => $status['id'] ?? '',
                    'from' => $status['recipient_id'] ?? '',
                    'read_at' => $status['timestamp'] ?? time()
                ]);
                break;
            case 'failed':
                $triggers_registry->trigger('whatsapp_message_failed', array_merge($data, [
                    'error_code' => $status['errors'][0]['code'] ?? '',
                    'error_message' => $status['errors'][0]['title'] ?? 'Message failed',
                    'failed_at' => $status['timestamp'] ?? time()
                ]));
                break;
        }
    }
}

