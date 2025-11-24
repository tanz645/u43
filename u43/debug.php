<?php
/**
 * Debug Helper - Remove this file in production
 * 
 * Add ?u43_debug=1 to any admin URL to see debug info
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function() {
    if (isset($_GET['u43_debug']) && current_user_can('manage_options')) {
        echo '<pre>';
        echo "U43 Debug Information\n";
        echo "=====================\n\n";
        
        echo "Plugin Directory: " . U43_PLUGIN_DIR . "\n";
        echo "Plugin URL: " . U43_PLUGIN_URL . "\n";
        echo "Version: " . U43_VERSION . "\n\n";
        
        echo "Checking classes:\n";
        echo "- Core class exists: " . (class_exists('U43\\Core') ? 'YES' : 'NO') . "\n";
        echo "- Admin class exists: " . (class_exists('U43\\Admin\\Admin') ? 'YES' : 'NO') . "\n";
        echo "- Database class exists: " . (class_exists('U43\\Database\\Database') ? 'YES' : 'NO') . "\n\n";
        
        echo "Checking files:\n";
        echo "- Admin file exists: " . (file_exists(U43_PLUGIN_DIR . 'admin/class-admin.php') ? 'YES' : 'NO') . "\n";
        echo "- Database file exists: " . (file_exists(U43_PLUGIN_DIR . 'database/class-database.php') ? 'YES' : 'NO') . "\n\n";
        
        echo "Plugin active: " . (is_plugin_active('u43/u43.php') ? 'YES' : 'NO') . "\n";
        echo "Is admin: " . (is_admin() ? 'YES' : 'NO') . "\n";
        
        if (function_exists('U43')) {
            echo "\nU43() function exists: YES\n";
            try {
                $instance = U43();
                echo "U43() returns instance: " . (is_object($instance) ? 'YES' : 'NO') . "\n";
            } catch (\Exception $e) {
                echo "Error calling U43(): " . $e->getMessage() . "\n";
            }
        } else {
            echo "\nU43() function exists: NO\n";
        }
        
        echo '</pre>';
        exit;
    }
});

