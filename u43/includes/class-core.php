<?php
/**
 * Core Plugin Class
 *
 * @package U43
 */

namespace U43;

use U43\Registry\Tools_Registry;
use U43\Registry\Agents_Registry;
use U43\Registry\Triggers_Registry;
use U43\Flow_Manager;
use U43\Executor;
use U43\Database\Database;
use U43\Admin\Admin;
use U43\API\REST_API;

class Core {
    
    private static $instance = null;
    
    private $tools_registry;
    private $agents_registry;
    private $triggers_registry;
    private $flow_manager;
    private $executor;
    
    /**
     * Get singleton instance
     *
     * @return Core
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Initialize registries
        $this->tools_registry = new Tools_Registry();
        $this->agents_registry = new Agents_Registry();
        $this->triggers_registry = new Triggers_Registry();
        
        // Initialize managers
        $this->flow_manager = new Flow_Manager();
        $this->executor = new Executor($this->tools_registry, $this->agents_registry);
        
        // Load configurations
        try {
            $this->load_configurations();
        } catch (\Exception $e) {
            error_log('U43: Error loading configurations - ' . $e->getMessage());
        }
        
        // Initialize REST API
        try {
            new REST_API();
        } catch (\Exception $e) {
            error_log('U43: Error initializing REST API - ' . $e->getMessage());
        }
        
        // Initialize admin
        if (is_admin()) {
            try {
                new Admin();
            } catch (\Exception $e) {
                error_log('U43: Error initializing admin - ' . $e->getMessage());
            }
        }
        
        // Hook into WordPress
        $this->init_hooks();
    }
    
    /**
     * Load all configurations
     */
    private function load_configurations() {
        $config_loader = new Config\Config_Loader(
            $this->tools_registry,
            $this->agents_registry,
            $this->triggers_registry
        );
        $config_loader->load_all();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register comment trigger
        add_action('comment_post', [$this, 'handle_comment_post'], 10, 2);
    }
    
    /**
     * Handle comment post event
     *
     * @param int $comment_id Comment ID
     * @param int|string $comment_approved Approval status
     */
    public function handle_comment_post($comment_id, $comment_approved) {
        $comment = get_comment($comment_id);
        if (!$comment) {
            error_log("U43: Comment post hook fired but comment {$comment_id} not found");
            return;
        }
        
        error_log("U43: Comment post hook fired for comment ID {$comment_id} on post ID {$comment->comment_post_ID}");
        
        // Trigger workflows that listen to comment_post
        $this->triggers_registry->trigger('wordpress_comment_post', [
            'comment_id' => $comment_id,
            'comment' => $comment,
            'post_id' => $comment->comment_post_ID,
            'author' => $comment->comment_author,
            'content' => $comment->comment_content,
            'email' => $comment->comment_author_email,
        ]);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        Database::create_tables();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }
    
    /**
     * Get tools registry
     *
     * @return Tools_Registry
     */
    public function get_tools_registry() {
        return $this->tools_registry;
    }
    
    /**
     * Get agents registry
     *
     * @return Agents_Registry
     */
    public function get_agents_registry() {
        return $this->agents_registry;
    }
    
    /**
     * Get triggers registry
     *
     * @return Triggers_Registry
     */
    public function get_triggers_registry() {
        return $this->triggers_registry;
    }
    
    /**
     * Get flow manager
     *
     * @return Flow_Manager
     */
    public function get_flow_manager() {
        return $this->flow_manager;
    }
    
    /**
     * Get executor
     *
     * @return Executor
     */
    public function get_executor() {
        return $this->executor;
    }
}

