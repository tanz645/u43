<?php
/**
 * Plugin Name: U43 - WordPress Agentic Workflow
 * Plugin URI: https://github.com/your-org/u43
 * Description: Visual workflow automation with AI agents for WordPress
 * Version: 1.0.0
 * Author: Saidul Islam Bhuiyan <tanzibbdc@gmail.com>
 * License: GPL v2 or later
 * Text Domain: u43
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('U43_VERSION', '1.0.0');
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

