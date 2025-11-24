<?php
/**
 * Error Check Script
 * 
 * Upload this file to your WordPress root and access it via browser
 * to check for plugin errors before activation
 */

// Load WordPress
require_once('wp-load.php');

echo '<h1>U43 Plugin Error Check</h1>';
echo '<pre>';

// Check if constants are defined
echo "Checking constants:\n";
echo "- U43_PLUGIN_DIR: " . (defined('U43_PLUGIN_DIR') ? U43_PLUGIN_DIR : 'NOT DEFINED') . "\n";
echo "- U43_VERSION: " . (defined('U43_VERSION') ? U43_VERSION : 'NOT DEFINED') . "\n\n";

// Check if main file exists
$main_file = U43_PLUGIN_DIR . 'u43.php';
echo "Checking main file:\n";
echo "- Main file exists: " . (file_exists($main_file) ? 'YES' : 'NO') . "\n";
echo "- Main file path: " . $main_file . "\n\n";

// Check critical files
$critical_files = [
    'includes/class-autoloader.php',
    'includes/class-core.php',
    'database/class-database.php',
    'admin/class-admin.php',
];

echo "Checking critical files:\n";
foreach ($critical_files as $file) {
    $path = U43_PLUGIN_DIR . $file;
    $exists = file_exists($path);
    echo "- {$file}: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
    if (!$exists) {
        echo "  ERROR: File not found at: {$path}\n";
    }
}
echo "\n";

// Check if classes can be loaded
echo "Checking class loading:\n";
if (file_exists(U43_PLUGIN_DIR . 'includes/class-autoloader.php')) {
    require_once U43_PLUGIN_DIR . 'includes/class-autoloader.php';
    
    $classes_to_check = [
        'U43\\Core',
        'U43\\Database\\Database',
        'U43\\Admin\\Admin',
        'U43\\Registry\\Tools_Registry',
    ];
    
    foreach ($classes_to_check as $class) {
        $exists = class_exists($class);
        echo "- {$class}: " . ($exists ? 'LOADED' : 'NOT FOUND') . "\n";
    }
}

echo "\nDone!\n";
echo '</pre>';

