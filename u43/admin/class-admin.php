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
        
        // Load integration handlers (modular architecture)
        $this->load_handlers();
    }
    
    /**
     * Load integration handlers
     * This allows each integration to have its own handler class
     * Follows a modular architecture pattern for scalability
     */
    private function load_handlers() {
        $handlers_dir = U43_PLUGIN_DIR . 'admin/handlers/';
        
        if (!is_dir($handlers_dir)) {
            return;
        }
        
        // Get all handler files matching the pattern: class-*-handler.php
        $handler_files = glob($handlers_dir . 'class-*-handler.php');
        
        foreach ($handler_files as $handler_file) {
            // Require the file first to ensure class is loaded
            require_once $handler_file;
            
            // Get handler class name from file path
            $handler_class = $this->get_handler_class_name($handler_file);
            
            // Instantiate the handler class if it exists
            if ($handler_class && class_exists($handler_class)) {
                new $handler_class();
            }
        }
    }
    
    /**
     * Get handler class name from file path
     *
     * @param string $file_path File path
     * @return string|null Class name or null if not found
     */
    private function get_handler_class_name($file_path) {
        $filename = basename($file_path, '.php');
        
        // Convert filename to class name
        // e.g., class-whatsapp-handler.php -> U43\Admin\Handlers\WhatsApp_Handler
        $parts = explode('-', $filename);
        array_shift($parts); // Remove 'class'
        array_pop($parts); // Remove 'handler'
        
        // Convert to PascalCase with underscores
        $class_name = '';
        foreach ($parts as $part) {
            $class_name .= ucfirst($part) . '_';
        }
        $class_name = rtrim($class_name, '_') . '_Handler';
        
        return 'U43\\Admin\\Handlers\\' . $class_name;
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
        
        // Campaigns submenu
        add_submenu_page(
            'u43',
            __('Campaigns', 'u43'),
            __('Campaigns', 'u43'),
            'manage_options',
            'u43-campaigns',
            [$this, 'render_campaigns']
        );
        
        add_submenu_page(
            'u43',
            __('Contacts', 'u43'),
            __('Contacts', 'u43'),
            'manage_options',
            'u43-contacts',
            [$this, 'render_contacts']
        );
        
        add_submenu_page(
            'u43',
            __('Segments', 'u43'),
            __('Segments', 'u43'),
            'manage_options',
            'u43-segments',
            [$this, 'render_segments']
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
            case 'publish_workflow':
                $this->handle_publish_workflow();
                break;
            case 'unpublish_workflow':
                $this->handle_unpublish_workflow();
                break;
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
     * Handle publish workflow
     */
    private function handle_publish_workflow() {
        $workflow_id = intval($_POST['workflow_id'] ?? 0);
        if ($workflow_id) {
            $flow_manager = U43()->get_flow_manager();
            $result = $flow_manager->update_workflow($workflow_id, ['status' => 'published']);
            
            if ($result) {
                wp_redirect(admin_url('admin.php?page=u43&message=published'));
                exit;
            }
        }
        wp_redirect(admin_url('admin.php?page=u43&message=publish_failed'));
        exit;
    }
    
    /**
     * Handle unpublish workflow
     */
    private function handle_unpublish_workflow() {
        $workflow_id = intval($_POST['workflow_id'] ?? 0);
        if ($workflow_id) {
            $flow_manager = U43()->get_flow_manager();
            $result = $flow_manager->update_workflow($workflow_id, ['status' => 'draft']);
            
            if ($result) {
                wp_redirect(admin_url('admin.php?page=u43&message=unpublished'));
                exit;
            }
        }
        wp_redirect(admin_url('admin.php?page=u43&message=unpublish_failed'));
        exit;
    }
    
    /**
     * Render workflow list
     */
    public function render_workflow_list() {
        include U43_PLUGIN_DIR . 'admin/views/workflow-list.php';
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
        // Handle manual cleanup trigger
        if (isset($_POST['u43_manual_cleanup']) && check_admin_referer('u43_manual_cleanup')) {
            $result = \U43\Log_Cleanup::manual_cleanup();
            set_transient('u43_last_cleanup_result', $result, 300);
            
            if ($result['success']) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }
        
        // Handle log retention settings
        if (isset($_POST['u43_save_log_retention']) && check_admin_referer('u43_log_retention')) {
            $enabled = isset($_POST['log_retention_enabled']) ? (bool) $_POST['log_retention_enabled'] : false;
            $duration = isset($_POST['log_retention_duration']) ? intval($_POST['log_retention_duration']) : 7;
            $unit = isset($_POST['log_retention_unit']) ? sanitize_text_field($_POST['log_retention_unit']) : 'day';
            
            // Validate unit
            if (!in_array($unit, ['minute', 'hour', 'day'])) {
                $unit = 'day';
            }
            
            // Validate duration
            if ($duration < 1) {
                $duration = 1;
            }
            
            \U43\Log_Cleanup::update_settings([
                'enabled' => $enabled,
                'duration' => $duration,
                'unit' => $unit,
            ]);
            
            echo '<div class="notice notice-success"><p>' . esc_html__('Log retention settings saved!', 'u43') . '</p></div>';
        }
        
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
        // Handle OpenAI settings
        if (isset($_POST['u43_save_settings']) && check_admin_referer('u43_settings')) {
            $api_key = sanitize_text_field($_POST['openai_api_key'] ?? '');
            // Save even if empty (allows clearing the key)
            $result = \U43\Config\Settings_Manager::set('u43_openai_api_key', $api_key, 'string', true);
            if ($result) {
                if ($api_key) {
                    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved!', 'u43') . '</p></div>';
                } else {
                    echo '<div class="notice notice-warning"><p>' . esc_html__('API key cleared.', 'u43') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error saving settings. Please check if the settings table exists.', 'u43') . '</p></div>';
            }
        }
        
        // Handle WhatsApp settings
        if (isset($_POST['u43_save_whatsapp_settings']) && check_admin_referer('u43_whatsapp_settings')) {
            $phone_number = sanitize_text_field($_POST['whatsapp_phone_number'] ?? '');
            $phone_number_id = sanitize_text_field($_POST['whatsapp_phone_number_id'] ?? '');
            $api_token = sanitize_text_field($_POST['whatsapp_api_token'] ?? '');
            $business_id = sanitize_text_field($_POST['whatsapp_business_id'] ?? '');
            $webhook_url = esc_url_raw($_POST['whatsapp_webhook_url'] ?? '');
            $webhook_verify_token = sanitize_text_field($_POST['whatsapp_webhook_verify_token'] ?? '');
            $auth_method = sanitize_text_field($_POST['whatsapp_auth_method'] ?? 'phone_token');
            
            // Save settings
            $results = [
                \U43\Config\Settings_Manager::set('u43_whatsapp_phone_number', $phone_number, 'string'),
                \U43\Config\Settings_Manager::set('u43_whatsapp_phone_number_id', $phone_number_id, 'string'),
                \U43\Config\Settings_Manager::set('u43_whatsapp_api_token', $api_token, 'string', true),
                \U43\Config\Settings_Manager::set('u43_whatsapp_business_id', $business_id, 'string'),
                \U43\Config\Settings_Manager::set('u43_whatsapp_webhook_url', $webhook_url, 'string'),
                \U43\Config\Settings_Manager::set('u43_whatsapp_webhook_verify_token', $webhook_verify_token, 'string', true),
                \U43\Config\Settings_Manager::set('u43_whatsapp_auth_method', $auth_method, 'string'),
            ];
            
            if (in_array(false, $results, true)) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error saving some settings. Please check if the settings table exists.', 'u43') . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . esc_html__('WhatsApp settings saved!', 'u43') . '</p></div>';
            }
        }
        
        include U43_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render campaigns page
     */
    public function render_campaigns() {
        include U43_PLUGIN_DIR . 'admin/views/campaigns.php';
    }
    
    /**
     * Render contacts page
     */
    public function render_contacts() {
        include U43_PLUGIN_DIR . 'admin/views/contacts.php';
    }
    
    /**
     * Render segments page
     */
    public function render_segments() {
        include U43_PLUGIN_DIR . 'admin/views/segments.php';
    }
    
}

