<?php
/**
 * Settings Manager Class
 *
 * Manages plugin settings stored in custom settings table instead of wp_options
 *
 * @package U43
 */

namespace U43\Config;

class Settings_Manager {
    
    /**
     * Get a setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed Setting value or default
     */
    public static function get($key, $default = '') {
        global $wpdb;
        
        $settings_table = $wpdb->prefix . 'u43_settings';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table;
        if (!$table_exists) {
            // Table doesn't exist yet - try to create it
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            \U43\Database\Database::create_tables();
            
            // Check again
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table;
            if (!$table_exists) {
                // Still doesn't exist
                return $default;
            }
        }
        
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT setting_value, setting_type, is_encrypted FROM $settings_table WHERE setting_key = %s",
            $key
        ), ARRAY_A);
        
        if ($row === null) {
            // Setting doesn't exist
            return $default;
        }
        
        $value = $row['setting_value'];
        $type = $row['setting_type'];
        $is_encrypted = (bool) $row['is_encrypted'];
        
        // Decrypt first if needed
        if ($is_encrypted && !empty($value)) {
            $value = self::decrypt($value);
        }
        
        // Deserialize if needed
        $value = maybe_unserialize($value);
        
        return self::convert_type($value, $type);
    }
    
    /**
     * Set a setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $type Setting type (string, int, float, bool, array, json)
     * @param bool $encrypted Whether the value should be encrypted
     * @return bool Success status
     */
    public static function set($key, $value, $type = 'string', $encrypted = false) {
        global $wpdb;
        
        $settings_table = $wpdb->prefix . 'u43_settings';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table;
        if (!$table_exists) {
            // Table doesn't exist yet - try to create it
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            \U43\Database\Database::create_tables();
            
            // Check again
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table;
            if (!$table_exists) {
                // Still doesn't exist - log error for debugging
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("U43 Settings Manager: Table $settings_table does not exist and could not be created.");
                }
                return false;
            }
        }
        
        // Auto-detect type if not specified
        if ($type === 'string') {
            $type = self::detect_type($value);
        }
        
        // Serialize if needed (for arrays/objects)
        $serialized_value = maybe_serialize($value);
        
        // Encrypt if needed (encrypt the serialized value)
        if ($encrypted && !empty($serialized_value)) {
            $serialized_value = self::encrypt($serialized_value);
        }
        
        $result = $wpdb->replace(
            $settings_table,
            [
                'setting_key' => $key,
                'setting_value' => $serialized_value,
                'setting_type' => $type,
                'is_encrypted' => $encrypted ? 1 : 0,
            ],
            ['%s', '%s', '%s', '%d']
        );
        
        // Log error if insert failed
        if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("U43 Settings Manager: Failed to save setting '$key'. Error: " . $wpdb->last_error);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete a setting
     *
     * @param string $key Setting key
     * @return bool Success status
     */
    public static function delete($key) {
        global $wpdb;
        
        $settings_table = $wpdb->prefix . 'u43_settings';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$settings_table'") != $settings_table) {
            // Table doesn't exist yet
            return false;
        }
        
        $result = $wpdb->delete(
            $settings_table,
            ['setting_key' => $key],
            ['%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Get all settings
     *
     * @return array Array of settings (key => value)
     */
    public static function get_all() {
        global $wpdb;
        
        $settings_table = $wpdb->prefix . 'u43_settings';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table;
        if (!$table_exists) {
            // Try to create it
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            \U43\Database\Database::create_tables();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table;
            if (!$table_exists) {
                return [];
            }
        }
        
        $results = $wpdb->get_results(
            "SELECT setting_key, setting_value, setting_type, is_encrypted FROM $settings_table",
            ARRAY_A
        );
        
        $settings = [];
        foreach ($results as $row) {
            $value = $row['setting_value'];
            $is_encrypted = (bool) $row['is_encrypted'];
            
            // Decrypt first if needed
            if ($is_encrypted && !empty($value)) {
                $value = self::decrypt($value);
            }
            
            // Deserialize if needed
            $value = maybe_unserialize($value);
            
            $settings[$row['setting_key']] = self::convert_type($value, $row['setting_type']);
        }
        
        return $settings;
    }
    
    /**
     * Get settings by prefix
     *
     * @param string $prefix Setting key prefix
     * @return array Array of settings (key => value)
     */
    public static function get_by_prefix($prefix) {
        global $wpdb;
        
        $settings_table = $wpdb->prefix . 'u43_settings';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table;
        if (!$table_exists) {
            // Try to create it
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            \U43\Database\Database::create_tables();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$settings_table'") == $settings_table;
            if (!$table_exists) {
                return [];
            }
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT setting_key, setting_value, setting_type, is_encrypted FROM $settings_table WHERE setting_key LIKE %s",
            $prefix . '%'
        ), ARRAY_A);
        
        $settings = [];
        foreach ($results as $row) {
            $value = $row['setting_value'];
            $is_encrypted = (bool) $row['is_encrypted'];
            
            // Decrypt first if needed
            if ($is_encrypted && !empty($value)) {
                $value = self::decrypt($value);
            }
            
            // Deserialize if needed
            $value = maybe_unserialize($value);
            
            $settings[$row['setting_key']] = self::convert_type($value, $row['setting_type']);
        }
        
        return $settings;
    }
    
    /**
     * Delete all settings
     *
     * @return bool Success status
     */
    public static function delete_all() {
        global $wpdb;
        
        $settings_table = $wpdb->prefix . 'u43_settings';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$settings_table'") != $settings_table) {
            return false;
        }
        
        $result = $wpdb->query("TRUNCATE TABLE $settings_table");
        
        return $result !== false;
    }
    
    /**
     * Detect the type of a value
     *
     * @param mixed $value Value to detect type for
     * @return string Type (string, int, float, bool, array, json)
     */
    private static function detect_type($value) {
        if (is_bool($value)) {
            return 'bool';
        } elseif (is_int($value)) {
            return 'int';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value) || is_object($value)) {
            return 'array';
        } else {
            return 'string';
        }
    }
    
    /**
     * Convert value to specified type
     *
     * @param mixed $value Value to convert
     * @param string $type Target type
     * @return mixed Converted value
     */
    private static function convert_type($value, $type) {
        switch ($type) {
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'array':
                if (is_string($value)) {
                    return maybe_unserialize($value);
                }
                return (array) $value;
            case 'json':
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return $decoded !== null ? $decoded : $value;
                }
                return $value;
            default:
                return $value;
        }
    }
    
    /**
     * Encrypt a value
     *
     * @param string $value Value to encrypt
     * @return string Encrypted value
     */
    private static function encrypt($value) {
        // Use WordPress's built-in encryption if available
        if (function_exists('wp_encrypt')) {
            return wp_encrypt($value);
        }
        
        // Fallback: simple base64 encoding (not secure, but better than plain text)
        // In production, you should use proper encryption
        return base64_encode($value);
    }
    
    /**
     * Decrypt a value
     *
     * @param string $value Encrypted value
     * @return string Decrypted value
     */
    private static function decrypt($value) {
        // Use WordPress's built-in decryption if available
        if (function_exists('wp_decrypt')) {
            return wp_decrypt($value);
        }
        
        // Fallback: simple base64 decoding
        $decoded = base64_decode($value, true);
        return $decoded !== false ? $decoded : $value;
    }
}

