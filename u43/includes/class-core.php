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
    
    // Track deleted products to prevent duplicate triggers
    private static $deleted_products = [];
    
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
        
        // Register WooCommerce product published trigger
        // Hook into init to ensure WooCommerce is loaded
        add_action('init', [$this, 'register_woocommerce_hooks'], 20);
    }
    
    /**
     * Register WooCommerce hooks after WooCommerce is loaded
     * 
     * References:
     * - https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/
     * - https://woocommerce.github.io/code-reference/hooks/hooks.html
     */
    public function register_woocommerce_hooks() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            error_log('U43: WooCommerce not detected, skipping hook registration');
            return;
        }
        
        // Use transition_post_status hook - fires when post status changes
        // This is more reliable than save_post_product for detecting new publications
        add_action('transition_post_status', [$this, 'handle_post_status_transition'], 10, 3);
        
        // Also use save_post_product as backup
        add_action('save_post_product', [$this, 'handle_product_save'], 10, 3);
        
        // Register product deletion hooks
        // wp_trash_post fires when product is moved to trash (soft delete)
        add_action('wp_trash_post', [$this, 'handle_product_trash'], 10, 1);
        
        // before_delete_post fires before product is permanently deleted, allowing us to capture product data
        add_action('before_delete_post', [$this, 'handle_product_deletion'], 10, 1);
        
        // woocommerce_delete_product is a WooCommerce-specific hook that fires when product is deleted
        add_action('woocommerce_delete_product', [$this, 'handle_woocommerce_product_deletion'], 10, 1);
        
        error_log('U43: WooCommerce hooks registered');
    }
    
    /**
     * Handle post status transition - detects when product is published
     * This hook fires when a post transitions from one status to another
     * 
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param \WP_Post $post Post object
     */
    public function handle_post_status_transition($new_status, $old_status, $post) {
        // Only trigger when transitioning TO publish status
        if ($new_status !== 'publish') {
            return;
        }
        
        // Only trigger for product post type
        if ($post->post_type !== 'product') {
            return;
        }
        
        // Skip if already published (this is an update, not a new publication)
        if ($old_status === 'publish') {
            return;
        }
        
        error_log("U43: transition_post_status hook fired - Product ID: {$post->ID}, Status: {$old_status} -> {$new_status}");
        
        // Get WooCommerce product object
        if (!function_exists('wc_get_product')) {
            return;
        }
        
        $product = wc_get_product($post->ID);
        if (!$product) {
            return;
        }
        
        error_log("U43: WooCommerce product published via transition_post_status - Product ID: {$post->ID} ({$product->get_name()})");
        
        // Prepare trigger data
        $trigger_data = [
            'product_id' => $post->ID,
            'product_name' => $product->get_name(),
            'product_sku' => $product->get_sku() ?: '',
            'product_price' => $product->get_price() ?: '0',
            'product_type' => $product->get_type(),
            'product_status' => $product->get_status(),
            'product_url' => $product->get_permalink(),
            'product_author' => (int)$post->post_author,
            'product_created_date' => $post->post_date,
            'product_modified_date' => $post->post_modified,
        ];
        
        // Trigger the workflow
        $this->triggers_registry->trigger('woocommerce_product_published', $trigger_data);
    }
    
    /**
     * Handle WooCommerce product save event
     * Only triggers for newly published products (not updates)
     * 
     * References:
     * - https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/
     * - https://woocommerce.github.io/code-reference/hooks/hooks.html
     * - Uses save_post_product hook (recommended over general save_post)
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function handle_product_save($post_id, $post, $update) {
        error_log("U43: save_post_product hook fired - Post ID: {$post_id}, Update: " . ($update ? 'true' : 'false') . ", Status: " . ($post->post_status ?? 'unknown'));
        
        // Skip autosaves, revisions, and non-product posts
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log("U43: Skipping - DOING_AUTOSAVE");
            return;
        }
        
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            error_log("U43: Skipping - revision or autosave");
            return;
        }
        
        // Verify this is actually a product
        if ($post->post_type !== 'product') {
            error_log("U43: Skipping - not a product post type");
            return;
        }
        
        // Only trigger for published products
        if ($post->post_status !== 'publish') {
            error_log("U43: Skipping - post status is not 'publish' (status: {$post->post_status})");
            return;
        }
        
        // Better detection: Check if this is a new product by comparing dates
        // For new products, post_date and post_modified should be very close (within 5 seconds)
        $created_time = strtotime($post->post_date);
        $modified_time = strtotime($post->post_modified);
        $time_diff = abs($modified_time - $created_time);
        
        // Also check if $update is false (but this can be unreliable)
        $is_new_product = !$update && $time_diff <= 5;
        
        // Additional check: if post was just created (within last 10 seconds), consider it new
        $now = time();
        $created_recently = ($now - $created_time) <= 10;
        
        if (!$is_new_product && !$created_recently) {
            error_log("U43: Skipping - appears to be an update (update={$update}, time_diff={$time_diff}s, created_recently=" . ($created_recently ? 'true' : 'false') . ")");
            return;
        }
        
        // Get WooCommerce product object
        if (!function_exists('wc_get_product')) {
            error_log("U43: Skipping - wc_get_product function not available");
            return;
        }
        
        $product = wc_get_product($post_id);
        if (!$product) {
            error_log("U43: Skipping - product object not found");
            return;
        }
        
        error_log("U43: WooCommerce product published hook fired for product ID {$post_id} ({$product->get_name()})");
        
        // Prepare trigger data
        $trigger_data = [
            'product_id' => $post_id,
            'product_name' => $product->get_name(),
            'product_sku' => $product->get_sku() ?: '',
            'product_price' => $product->get_price() ?: '0',
            'product_type' => $product->get_type(),
            'product_status' => $product->get_status(),
            'product_url' => $product->get_permalink(),
            'product_author' => (int)$post->post_author,
            'product_created_date' => $post->post_date,
            'product_modified_date' => $post->post_modified,
        ];
        
        // Trigger the workflow
        $this->triggers_registry->trigger('woocommerce_product_published', $trigger_data);
    }
    
    /**
     * Handle product trash via wp_trash_post hook
     * This hook fires when a product is moved to trash (soft delete)
     * 
     * References:
     * - https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/
     * - https://woocommerce.github.io/code-reference/hooks/hooks.html
     *
     * @param int $post_id Post ID being trashed
     */
    public function handle_product_trash($post_id) {
        // Only process product post type
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Prevent duplicate triggers if both trash and delete hooks fire
        if (isset(self::$deleted_products[$post_id])) {
            error_log("U43: Product {$post_id} deletion already processed, skipping trash handler");
            return;
        }
        
        error_log("U43: wp_trash_post hook fired for product ID: {$post_id}");
        
        // Get post data before it's trashed
        $post = get_post($post_id);
        if (!$post) {
            error_log("U43: Product post {$post_id} not found");
            return;
        }
        
        // Get WooCommerce product object before it's trashed
        if (!function_exists('wc_get_product')) {
            error_log("U43: wc_get_product function not available");
            return;
        }
        
        $product = wc_get_product($post_id);
        if (!$product) {
            error_log("U43: Product object not found for product ID {$post_id}");
            return;
        }
        
        error_log("U43: WooCommerce product trashed via wp_trash_post - Product ID: {$post_id} ({$product->get_name()})");
        
        // Mark as processed to prevent duplicate triggers
        self::$deleted_products[$post_id] = true;
        
        // Prepare trigger data
        $trigger_data = [
            'product_id' => $post_id,
            'product_name' => $product->get_name(),
            'product_sku' => $product->get_sku() ?: '',
            'product_price' => $product->get_price() ?: '0',
            'product_type' => $product->get_type(),
            'product_status' => $product->get_status(),
            'product_url' => $product->get_permalink(),
            'product_author' => (int)$post->post_author,
            'product_created_date' => $post->post_date,
            'product_modified_date' => $post->post_modified,
            'deleted_date' => current_time('mysql'),
        ];
        
        // Trigger the workflow
        $this->triggers_registry->trigger('woocommerce_product_deleted', $trigger_data);
    }
    
    /**
     * Handle product deletion via before_delete_post hook
     * This hook fires before the product is deleted, allowing us to capture product data
     * 
     * References:
     * - https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/
     * - https://woocommerce.github.io/code-reference/hooks/hooks.html
     *
     * @param int $post_id Post ID being deleted
     */
    public function handle_product_deletion($post_id) {
        // Only process product post type
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Prevent duplicate triggers if both hooks fire
        if (isset(self::$deleted_products[$post_id])) {
            error_log("U43: Product {$post_id} deletion already processed, skipping duplicate trigger");
            return;
        }
        
        error_log("U43: before_delete_post hook fired for product ID: {$post_id}");
        
        // Get post data before deletion
        $post = get_post($post_id);
        if (!$post) {
            error_log("U43: Product post {$post_id} not found");
            return;
        }
        
        // Get WooCommerce product object before deletion
        if (!function_exists('wc_get_product')) {
            error_log("U43: wc_get_product function not available");
            return;
        }
        
        $product = wc_get_product($post_id);
        if (!$product) {
            error_log("U43: Product object not found for product ID {$post_id}");
            return;
        }
        
        error_log("U43: WooCommerce product deleted via before_delete_post - Product ID: {$post_id} ({$product->get_name()})");
        
        // Mark as processed to prevent duplicate triggers
        self::$deleted_products[$post_id] = true;
        
        // Prepare trigger data
        $trigger_data = [
            'product_id' => $post_id,
            'product_name' => $product->get_name(),
            'product_sku' => $product->get_sku() ?: '',
            'product_price' => $product->get_price() ?: '0',
            'product_type' => $product->get_type(),
            'product_status' => $product->get_status(),
            'product_url' => $product->get_permalink(),
            'product_author' => (int)$post->post_author,
            'product_created_date' => $post->post_date,
            'product_modified_date' => $post->post_modified,
            'deleted_date' => current_time('mysql'),
        ];
        
        // Trigger the workflow
        $this->triggers_registry->trigger('woocommerce_product_deleted', $trigger_data);
    }
    
    /**
     * Handle product deletion via woocommerce_delete_product hook
     * This is a WooCommerce-specific hook that fires when a product is deleted
     * 
     * References:
     * - https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/
     * - https://woocommerce.github.io/code-reference/hooks/hooks.html
     *
     * @param int $product_id Product ID being deleted
     */
    public function handle_woocommerce_product_deletion($product_id) {
        // Prevent duplicate triggers if both hooks fire
        if (isset(self::$deleted_products[$product_id])) {
            error_log("U43: Product {$product_id} deletion already processed via before_delete_post, skipping woocommerce_delete_product handler");
            return;
        }
        
        error_log("U43: woocommerce_delete_product hook fired for product ID: {$product_id}");
        
        // Try to get product data, but it may already be deleted
        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'product') {
            // Product already deleted, skip (main handler should have caught it)
            error_log("U43: Product {$product_id} already deleted, skipping woocommerce_delete_product handler");
            return;
        }
        
        // If we reach here, the product still exists, so trigger via main handler
        // This ensures we capture all product data properly
        $this->handle_product_deletion($product_id);
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

