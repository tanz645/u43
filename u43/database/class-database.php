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
        
        // Campaigns tables
        self::create_campaigns_tables($charset_collate);
    }
    
    /**
     * Create campaigns-related database tables
     *
     * @param string $charset_collate Database charset collate
     */
    private static function create_campaigns_tables($charset_collate) {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Folders table
        $folders_table = $wpdb->prefix . 'u43_campaign_folders';
        $sql = "CREATE TABLE $folders_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by BIGINT UNSIGNED,
            INDEX idx_name (name)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Tags table
        $tags_table = $wpdb->prefix . 'u43_campaign_tags';
        $sql = "CREATE TABLE $tags_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            color VARCHAR(7) DEFAULT '#0073aa',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Contacts table
        $contacts_table = $wpdb->prefix . 'u43_campaign_contacts';
        $sql = "CREATE TABLE $contacts_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            folder_id BIGINT UNSIGNED,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by BIGINT UNSIGNED,
            UNIQUE KEY unique_phone (phone),
            INDEX idx_folder_id (folder_id),
            INDEX idx_name (name),
            INDEX idx_phone (phone)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Contact tags relationship table
        $contact_tags_table = $wpdb->prefix . 'u43_campaign_contact_tags';
        $sql = "CREATE TABLE $contact_tags_table (
            contact_id BIGINT UNSIGNED NOT NULL,
            tag_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (contact_id, tag_id),
            INDEX idx_tag_id (tag_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Campaigns table
        $campaigns_table = $wpdb->prefix . 'u43_campaigns';
        $sql = "CREATE TABLE $campaigns_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            message_text TEXT,
            template_name VARCHAR(255),
            template_params LONGTEXT,
            target_type ENUM('all', 'tags', 'folder') DEFAULT 'all',
            target_value LONGTEXT,
            schedule_type ENUM('immediate', 'scheduled') DEFAULT 'immediate',
            scheduled_at DATETIME,
            status ENUM('draft', 'scheduled', 'running', 'completed', 'paused', 'cancelled') DEFAULT 'draft',
            batch_size INT DEFAULT 100,
            total_contacts INT DEFAULT 0,
            sent_count INT DEFAULT 0,
            failed_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            started_at DATETIME,
            completed_at DATETIME,
            created_by BIGINT UNSIGNED,
            INDEX idx_status (status),
            INDEX idx_scheduled_at (scheduled_at),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Campaign contacts table (tracks which contacts are in which campaign)
        $campaign_contacts_table = $wpdb->prefix . 'u43_campaign_contacts_rel';
        $sql = "CREATE TABLE $campaign_contacts_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id BIGINT UNSIGNED NOT NULL,
            contact_id BIGINT UNSIGNED NOT NULL,
            status ENUM('pending', 'sent', 'failed', 'delivered', 'read') DEFAULT 'pending',
            sent_at DATETIME,
            delivered_at DATETIME,
            read_at DATETIME,
            error_message TEXT,
            retry_count INT DEFAULT 0,
            whatsapp_message_id VARCHAR(255),
            INDEX idx_campaign_id (campaign_id),
            INDEX idx_contact_id (contact_id),
            INDEX idx_status (status),
            INDEX idx_sent_at (sent_at)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Campaign logs table (detailed logs per contact)
        $campaign_logs_table = $wpdb->prefix . 'u43_campaign_logs';
        $sql = "CREATE TABLE $campaign_logs_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id BIGINT UNSIGNED NOT NULL,
            contact_id BIGINT UNSIGNED NOT NULL,
            campaign_contact_id BIGINT UNSIGNED,
            log_type ENUM('info', 'success', 'error', 'warning') DEFAULT 'info',
            message TEXT NOT NULL,
            metadata LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_campaign_id (campaign_id),
            INDEX idx_contact_id (contact_id),
            INDEX idx_campaign_contact_id (campaign_contact_id),
            INDEX idx_log_type (log_type),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);
    }
}

