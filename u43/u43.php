<?php
/**
 * Plugin Name: U43 - WordPress Agentic Workflow
 * Plugin URI: https://github.com/your-org/u43
 * Description: Visual workflow automation with AI agents for WordPress
 * Version: 1.0.47
 * Author: Saidul Islam Bhuiyan <tanzibbdc@gmail.com>
 * License: GPL v2 or later
 * Text Domain: u43
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('U43_VERSION', '1.0.47');
define('U43_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('U43_PLUGIN_URL', plugin_dir_url(__FILE__));
define('U43_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('U43_PLUGIN_FILE', __FILE__);

// Autoloader
require_once U43_PLUGIN_DIR . 'includes/class-autoloader.php';

// Debug helper (remove in production)
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once U43_PLUGIN_DIR . 'debug.php';
}

// Initialize plugin
function U43() {
    try {
        return U43\Core::instance();
    } catch (\Exception $e) {
        error_log('U43 Plugin Error: ' . $e->getMessage());
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_die('U43 Plugin Error: ' . $e->getMessage());
        }
        return null;
    }
}

// Start the plugin
add_action('plugins_loaded', function() {
    try {
        U43();
    } catch (\Exception $e) {
        error_log('U43 Plugin Fatal Error: ' . $e->getMessage());
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p><strong>U43 Plugin Error:</strong> ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
}, 1);

// Activation hook
register_activation_hook(__FILE__, 'u43_activate');
function u43_activate() {
    // Ensure WordPress is loaded
    if (!function_exists('dbDelta')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }
    
    // Load Database class directly
    if (!class_exists('U43\\Database\\Database')) {
        $db_file = U43_PLUGIN_DIR . 'database/class-database.php';
        if (file_exists($db_file)) {
            require_once $db_file;
        } else {
            wp_die('U43 Plugin: Database class file not found. Please reinstall the plugin.');
        }
    }
    
    // Create tables
    if (class_exists('U43\\Database\\Database')) {
        U43\Database\Database::create_tables();
    }
    
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'u43_deactivate');
function u43_deactivate() {
    flush_rewrite_rules();
}

// Add plugin action links
add_filter('plugin_action_links_' . U43_PLUGIN_BASENAME, 'u43_add_plugin_action_links');
function u43_add_plugin_action_links($links) {
    $remove_data_url = wp_nonce_url(
        admin_url('plugins.php?u43_action=remove_all_data'),
        'u43_remove_all_data',
        'u43_nonce'
    );
    
    $links[] = '<a href="' . esc_url($remove_data_url) . '" class="u43-remove-all-data" style="color: #dc3232;">' . __('Remove All Data', 'u43') . '</a>';
    
    return $links;
}

// Handle remove all data action
add_action('admin_init', 'u43_handle_remove_all_data');
function u43_handle_remove_all_data() {
    // Only process if this is our action
    if (!isset($_GET['u43_action']) || $_GET['u43_action'] !== 'remove_all_data') {
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to perform this action.', 'u43'));
    }
    
    // Verify nonce
    if (!isset($_GET['u43_nonce']) || !wp_verify_nonce($_GET['u43_nonce'], 'u43_remove_all_data')) {
        wp_die(__('Security check failed.', 'u43'));
    }
    
    // Check if confirmed
    if (!isset($_GET['confirmed']) || $_GET['confirmed'] !== 'yes') {
        // Show confirmation notice on plugins page
        add_action('admin_notices', 'u43_show_remove_data_confirmation');
        return;
    }
    
    // Remove all data
    require_once U43_PLUGIN_DIR . 'includes/class-data-cleanup.php';
    $result = U43\Data_Cleanup::remove_all_data();
    
    // Set transient for success/error message
    if ($result['success']) {
        set_transient('u43_remove_data_success', $result['message'], 30);
    } else {
        set_transient('u43_remove_data_error', $result['message'], 30);
    }
    
    // Redirect to plugins page
    wp_safe_redirect(admin_url('plugins.php'));
    exit;
}

// Show success/error messages
add_action('admin_notices', 'u43_show_remove_data_messages');
function u43_show_remove_data_messages() {
    // Only show on plugins page
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'plugins') {
        return;
    }
    
    $success = get_transient('u43_remove_data_success');
    if ($success) {
        delete_transient('u43_remove_data_success');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($success) . '</p></div>';
    }
    
    $error = get_transient('u43_remove_data_error');
    if ($error) {
        delete_transient('u43_remove_data_error');
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
    }
}

// Show confirmation notice
function u43_show_remove_data_confirmation() {
    // Only show on plugins page
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'plugins') {
        return;
    }
    
    $confirm_url = wp_nonce_url(
        admin_url('plugins.php?u43_action=remove_all_data&confirmed=yes'),
        'u43_remove_all_data',
        'u43_nonce'
    );
    $cancel_url = admin_url('plugins.php');
    
    ?>
    <div class="notice notice-warning u43-remove-data-confirmation" style="border-left-color: #dc3232; margin-top: 20px;">
        <p><strong><?php esc_html_e('Remove All Plugin Data?', 'u43'); ?></strong></p>
        <p><?php esc_html_e('This will permanently delete:', 'u43'); ?></p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><?php esc_html_e('All workflows', 'u43'); ?></li>
            <li><?php esc_html_e('All execution logs', 'u43'); ?></li>
            <li><?php esc_html_e('All credentials and settings', 'u43'); ?></li>
            <li><?php esc_html_e('All cached data', 'u43'); ?></li>
        </ul>
        <p><strong style="color: #dc3232;"><?php esc_html_e('This action cannot be undone!', 'u43'); ?></strong></p>
        <p>
            <a href="<?php echo esc_url($confirm_url); ?>" class="button button-primary u43-confirm-remove" style="background-color: #dc3232; border-color: #dc3232;">
                <?php esc_html_e('Yes, Remove All Data', 'u43'); ?>
            </a>
            <a href="<?php echo esc_url($cancel_url); ?>" class="button">
                <?php esc_html_e('Cancel', 'u43'); ?>
            </a>
        </p>
    </div>
    <?php
}

// Enqueue script for browser storage cleanup
add_action('admin_enqueue_scripts', 'u43_enqueue_remove_data_script');
function u43_enqueue_remove_data_script($hook) {
    // Only on plugins page
    if ($hook !== 'plugins.php') {
        return;
    }
    
    wp_enqueue_script(
        'u43-remove-data',
        U43_PLUGIN_URL . 'admin/assets/js/remove-data.js',
        ['jquery'],
        U43_VERSION,
        true
    );
    
    wp_localize_script('u43-remove-data', 'u43RemoveData', [
        'confirmMessage' => __('Are you sure you want to remove all plugin data? This action cannot be undone!', 'u43'),
        'defaultNo' => __('No', 'u43'),
        'yesRemove' => __('Yes, Remove All Data', 'u43'),
    ]);
}

