<?php
/**
 * Autoloader for U43 Plugin
 *
 * @package U43
 */

namespace U43;

spl_autoload_register(function ($class) {
    // Only autoload classes from our namespace
    if (strpos($class, 'U43\\') !== 0) {
        return;
    }

    // Remove namespace prefix
    $class = str_replace('U43\\', '', $class);
    
    // Convert namespace separators to directory separators
    $parts = explode('\\', $class);
    
    // Convert each part: underscores to hyphens and lowercase
    $parts = array_map(function($part) {
        return strtolower(str_replace('_', '-', $part));
    }, $parts);
    
    // Add 'class-' prefix to the last part (class name)
    $last_index = count($parts) - 1;
    $parts[$last_index] = 'class-' . $parts[$last_index];
    
    // Special handling for classes in root directories
    if (count($parts) === 1 && $parts[0] === 'class-admin') {
        // Admin class is in admin/ directory (U43\Admin)
        $file = U43_PLUGIN_DIR . 'admin' . DIRECTORY_SEPARATOR . 'class-admin.php';
    } elseif (count($parts) === 2 && $parts[0] === 'admin' && $parts[1] === 'class-admin') {
        // Admin class is in admin/ directory (U43\Admin\Admin)
        $file = U43_PLUGIN_DIR . 'admin' . DIRECTORY_SEPARATOR . 'class-admin.php';
    } elseif (count($parts) === 3 && $parts[0] === 'admin' && $parts[1] === 'handlers') {
        // Admin handlers are in admin/handlers/ directory (U43\Admin\Handlers\*)
        $file = U43_PLUGIN_DIR . 'admin' . DIRECTORY_SEPARATOR . 'handlers' . DIRECTORY_SEPARATOR . $parts[2] . '.php';
    } elseif (count($parts) === 2 && $parts[0] === 'database' && $parts[1] === 'class-database') {
        // Database class is in database/ directory
        $file = U43_PLUGIN_DIR . 'database' . DIRECTORY_SEPARATOR . 'class-database.php';
    } else {
        // Build file path for includes/ directory
        $file = U43_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
    }
    
    // Load file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

