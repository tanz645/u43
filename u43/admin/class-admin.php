<?php
/**
 * Admin Class
 *
 * @package U43
 */

namespace U43\Admin;

class Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Workflows', 'u43'),
            __('Workflows', 'u43'),
            'manage_options',
            'u43',
            [$this, 'render_workflow_list'],
            'dashicons-networking',
            30
        );
        
        add_submenu_page(
            'u43',
            __('All Workflows', 'u43'),
            __('All Workflows', 'u43'),
            'manage_options',
            'u43',
            [$this, 'render_workflow_list']
        );
        
        add_submenu_page(
            'u43',
            __('Add New', 'u43'),
            __('Add New', 'u43'),
            'manage_options',
            'u43-new',
            [$this, 'render_workflow_form']
        );
        
        add_submenu_page(
            'u43',
            __('Workflow Builder', 'u43'),
            __('Workflow Builder', 'u43'),
            'manage_options',
            'u43-builder',
            [$this, 'render_workflow_builder']
        );
        
        add_submenu_page(
            'u43',
            __('Executions & Logs', 'u43'),
            __('Executions & Logs', 'u43'),
            'manage_options',
            'u43-executions',
            [$this, 'render_executions']
        );
        
        add_submenu_page(
            'u43',
            __('Templates', 'u43'),
            __('Templates', 'u43'),
            'manage_options',
            'u43-templates',
            [$this, 'render_templates']
        );
        
        add_submenu_page(
            'u43',
            __('Settings', 'u43'),
            __('Settings', 'u43'),
            'manage_options',
            'u43-settings',
            [$this, 'render_settings']
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'u43') === false) {
            return;
        }
        
        // Workflow builder page - load React app
        if ($hook === 'workflows_page_u43-builder') {
            $build_dir = U43_PLUGIN_DIR . 'admin/assets/dist/';
            
            // Find CSS file (Vite generates hashed filenames)
            $css_files = glob($build_dir . 'assets/*.css');
            if (!empty($css_files)) {
                $css_file = str_replace($build_dir, '', $css_files[0]);
                wp_enqueue_style('u43-workflow-builder', U43_PLUGIN_URL . 'admin/assets/dist/' . $css_file, [], U43_VERSION);
            }
            
            // Enqueue JS file
            if (file_exists($build_dir . 'workflowBuilder.js')) {
                wp_enqueue_script('u43-workflow-builder', U43_PLUGIN_URL . 'admin/assets/dist/workflowBuilder.js', [], U43_VERSION, true);
            }
        } else {
            // Other admin pages
            wp_enqueue_style('u43-admin', U43_PLUGIN_URL . 'admin/assets/css/admin.css', [], U43_VERSION);
            wp_enqueue_script('u43-admin', U43_PLUGIN_URL . 'admin/assets/js/admin.js', ['jquery'], U43_VERSION, true);
        }
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        // Handle export (GET request)
        if (isset($_GET['u43_action']) && $_GET['u43_action'] === 'export_workflow') {
            $this->handle_export_workflow();
            return;
        }
        
        if (!isset($_POST['u43_action']) || !check_admin_referer('u43_workflow_action')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['u43_action']);
        
        switch ($action) {
            case 'create_workflow':
                $this->handle_create_workflow();
                break;
            case 'update_workflow':
                $this->handle_update_workflow();
                break;
            case 'delete_workflow':
                $this->handle_delete_workflow();
                break;
            case 'duplicate_workflow':
                $this->handle_duplicate_workflow();
                break;
            case 'export_workflow':
                $this->handle_export_workflow();
                break;
        }
    }
    
    /**
     * Handle create workflow
     */
    private function handle_create_workflow() {
        $flow_manager = U43()->get_flow_manager();
        
        $workflow_data = [
            'title' => sanitize_text_field($_POST['title'] ?? 'Untitled Workflow'),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'draft'),
            'nodes' => [
                [
                    'id' => 'trigger_1',
                    'type' => 'trigger',
                    'trigger_type' => 'wordpress_comment_post',
                ],
                [
                    'id' => 'agent_1',
                    'type' => 'agent',
                    'agent_id' => 'llm_decision_agent',
                    'config' => [
                        'inputs' => [
                            'prompt' => 'Analyze this comment and decide: approve, spam, or delete. Comment: {{trigger_1.content}}',
                            'context' => [
                                'author' => '{{trigger_1.author}}',
                                'email' => '{{trigger_1.email}}',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'action_1',
                    'type' => 'action',
                    'action_type' => 'conditional',
                    'config' => [
                        'conditions' => [
                            ['if' => "decision == 'approve'", 'then' => 'wordpress_approve_comment'],
                            ['if' => "decision == 'spam'", 'then' => 'wordpress_spam_comment'],
                            ['if' => "decision == 'delete'", 'then' => 'wordpress_delete_comment'],
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['from' => 'trigger_1', 'to' => 'agent_1'],
                ['from' => 'agent_1', 'to' => 'action_1'],
            ],
        ];
        
        $workflow_id = $flow_manager->create_workflow($workflow_data);
        
        if ($workflow_id) {
            wp_redirect(admin_url('admin.php?page=u43&message=created'));
            exit;
        }
    }
    
    /**
     * Handle update workflow
     */
    private function handle_update_workflow() {
        // Implementation for update
    }
    
    /**
     * Handle delete workflow
     */
    private function handle_delete_workflow() {
        $workflow_id = intval($_POST['workflow_id'] ?? 0);
        if ($workflow_id) {
            $flow_manager = U43()->get_flow_manager();
            $flow_manager->delete_workflow($workflow_id);
            wp_redirect(admin_url('admin.php?page=u43&message=deleted'));
            exit;
        }
    }
    
    /**
     * Handle duplicate workflow
     */
    private function handle_duplicate_workflow() {
        $workflow_id = intval($_POST['workflow_id'] ?? 0);
        if ($workflow_id) {
            $flow_manager = U43()->get_flow_manager();
            $workflow = $flow_manager->get_workflow($workflow_id);
            
            if ($workflow) {
                $workflow_data = $workflow->workflow_data;
                $new_workflow_id = $flow_manager->create_workflow([
                    'title' => $workflow->title . ' (Copy)',
                    'description' => $workflow->description,
                    'status' => 'draft',
                    'nodes' => $workflow_data['nodes'] ?? [],
                    'edges' => $workflow_data['edges'] ?? [],
                ]);
                
                if ($new_workflow_id) {
                    wp_redirect(admin_url('admin.php?page=u43&message=duplicated'));
                    exit;
                }
            }
        }
        wp_redirect(admin_url('admin.php?page=u43&message=duplicate_failed'));
        exit;
    }
    
    /**
     * Handle export workflow
     */
    private function handle_export_workflow() {
        $workflow_id = intval($_GET['workflow_id'] ?? 0);
        if ($workflow_id) {
            $flow_manager = U43()->get_flow_manager();
            $workflow = $flow_manager->get_workflow($workflow_id);
            
            if ($workflow) {
                $export_data = [
                    'name' => $workflow->title,
                    'description' => $workflow->description,
                    'category' => 'Exported',
                    'nodes' => $workflow->workflow_data['nodes'] ?? [],
                    'edges' => $workflow->workflow_data['edges'] ?? [],
                ];
                
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="workflow-' . $workflow_id . '.json"');
                echo json_encode($export_data, JSON_PRETTY_PRINT);
                exit;
            }
        }
        wp_die('Workflow not found');
    }
    
    /**
     * Render workflow list
     */
    public function render_workflow_list() {
        include U43_PLUGIN_DIR . 'admin/views/workflow-list.php';
    }
    
    /**
     * Render workflow form
     */
    public function render_workflow_form() {
        include U43_PLUGIN_DIR . 'admin/views/workflow-form.php';
    }
    
    /**
     * Render workflow builder
     */
    public function render_workflow_builder() {
        include U43_PLUGIN_DIR . 'admin/views/workflow-builder.php';
    }
    
    /**
     * Render executions and logs
     */
    public function render_executions() {
        include U43_PLUGIN_DIR . 'admin/views/executions.php';
    }
    
    /**
     * Render templates
     */
    public function render_templates() {
        include U43_PLUGIN_DIR . 'admin/views/templates.php';
    }
    
    /**
     * Render settings
     */
    public function render_settings() {
        if (isset($_POST['u43_save_settings']) && check_admin_referer('u43_settings')) {
            $api_key = sanitize_text_field($_POST['openai_api_key'] ?? '');
            // Save even if empty (allows clearing the key)
            update_option('u43_openai_api_key', $api_key);
            if ($api_key) {
                echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved!', 'u43') . '</p></div>';
            } else {
                echo '<div class="notice notice-warning"><p>' . esc_html__('API key cleared.', 'u43') . '</p></div>';
            }
        }
        
        include U43_PLUGIN_DIR . 'admin/views/settings.php';
    }
}

