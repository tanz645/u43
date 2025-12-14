<?php
/**
 * Database Class
 *
 * @package U43
 */

namespace U43\Database;

class Database {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Workflows table
        $workflows_table = $wpdb->prefix . 'u43_workflows';
        $sql = "CREATE TABLE $workflows_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('draft', 'published', 'paused', 'archived') DEFAULT 'draft',
            workflow_data LONGTEXT NOT NULL,
            version INT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by BIGINT UNSIGNED,
            updated_by BIGINT UNSIGNED,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Executions table
        $executions_table = $wpdb->prefix . 'u43_executions';
        $sql = "CREATE TABLE $executions_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            workflow_id BIGINT UNSIGNED NOT NULL,
            status ENUM('running', 'success', 'failed', 'cancelled') DEFAULT 'running',
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            duration_ms INT,
            trigger_data LONGTEXT,
            result_data LONGTEXT,
            error_message TEXT,
            error_stack TEXT,
            executed_by BIGINT UNSIGNED,
            INDEX idx_workflow_id (workflow_id),
            INDEX idx_status (status),
            INDEX idx_started_at (started_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Node logs table
        $node_logs_table = $wpdb->prefix . 'u43_node_logs';
        $sql = "CREATE TABLE $node_logs_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            execution_id BIGINT UNSIGNED NOT NULL,
            node_id VARCHAR(100) NOT NULL,
            node_type VARCHAR(50) NOT NULL,
            node_title VARCHAR(255),
            status ENUM('running', 'success', 'failed', 'skipped') DEFAULT 'running',
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            duration_ms INT,
            input_data LONGTEXT,
            output_data LONGTEXT,
            error_message TEXT,
            error_stack TEXT,
            INDEX idx_execution_id (execution_id),
            INDEX idx_node_id (node_id),
            INDEX idx_status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Button message mappings table (for routing button clicks to workflows)
        $button_mappings_table = $wpdb->prefix . 'u43_button_message_mappings';
        $sql = "CREATE TABLE $button_mappings_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            message_id VARCHAR(255) NOT NULL,
            workflow_id BIGINT UNSIGNED NOT NULL,
            execution_id BIGINT UNSIGNED NOT NULL,
            node_id VARCHAR(100) NOT NULL,
            button_ids TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_message_id (message_id),
            INDEX idx_workflow_id (workflow_id),
            INDEX idx_execution_id (execution_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Credentials table
        $credentials_table = $wpdb->prefix . 'u43_credentials';
        $sql = "CREATE TABLE $credentials_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            integration_id VARCHAR(100) NOT NULL,
            instance_name VARCHAR(100) DEFAULT 'default',
            credential_key VARCHAR(100) NOT NULL,
            credential_value LONGTEXT NOT NULL,
            is_encrypted TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_integration (integration_id, instance_name)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
}

