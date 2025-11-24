# Phase 1 Implementation Guide: Simple Comment Workflow

This guide provides step-by-step instructions and code examples for implementing Phase 1: Simple Comment Workflow.

## Overview

Phase 1 implements a minimal workflow:
1. **Trigger**: Comment posted on a WordPress post
2. **AI Agent**: Analyzes the comment and makes a decision
3. **Action**: Executes based on the decision (approve, spam, delete, or send email)

---

## Step 1: Project Setup

### 1.1 Create Plugin Structure

```
wp-agentic-workflow/
├── wp-agentic-workflow.php
├── includes/
│   ├── class-core.php
│   ├── class-flow-manager.php
│   ├── class-executor.php
│   ├── registry/
│   │   ├── class-registry-base.php
│   │   ├── class-tools-registry.php
│   │   ├── class-agents-registry.php
│   │   └── class-triggers-registry.php
│   ├── triggers/
│   │   └── wordpress/
│   │       └── class-comment-trigger.php
│   ├── agents/
│   │   ├── class-agent-base.php
│   │   └── built-in/
│   │       └── llm-decision-agent/
│   │           └── class-llm-decision-agent.php
│   ├── tools/
│   │   ├── class-tool-base.php
│   │   └── built-in/
│   │       └── wordpress/
│   │           ├── class-approve-comment.php
│   │           ├── class-spam-comment.php
│   │           ├── class-delete-comment.php
│   │           └── class-send-email.php
│   ├── llm/
│   │   ├── class-llm-provider-base.php
│   │   └── providers/
│   │       └── openai/
│   │           └── class-openai-provider.php
│   └── config/
│       ├── class-config-loader.php
│       └── class-config-validator.php
├── admin/
│   ├── class-admin.php
│   └── views/
│       ├── workflow-list.php
│       └── workflow-form.php
├── configs/
│   ├── triggers/
│   │   └── wordpress-comment.json
│   ├── agents/
│   │   └── llm-decision-agent.json
│   ├── tools/
│   │   ├── wordpress-approve-comment.json
│   │   ├── wordpress-spam-comment.json
│   │   ├── wordpress-delete-comment.json
│   │   └── wordpress-send-email.json
│   └── integrations/
│       └── openai.json
└── database/
    └── class-database.php
```

### 1.2 Main Plugin File

**File**: `wp-agentic-workflow.php`

```php
<?php
/**
 * Plugin Name: WordPress Agentic Workflow
 * Plugin URI: https://github.com/your-org/wp-agentic-workflow
 * Description: Visual workflow automation with AI agents
 * Version: 0.1.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AW_VERSION', '0.1.0');
define('AW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AW_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once AW_PLUGIN_DIR . 'includes/class-autoloader.php';

// Initialize plugin
function WP_Agentic_Workflow() {
    return WP_Agentic_Workflow\Core::instance();
}

// Start the plugin
add_action('plugins_loaded', 'WP_Agentic_Workflow', 1);

// Activation hook
register_activation_hook(__FILE__, function() {
    WP_Agentic_Workflow()->activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    WP_Agentic_Workflow()->deactivate();
});
```

---

## Step 2: Database Setup

### 2.1 Database Class

**File**: `database/class-database.php`

```php
<?php
namespace WP_Agentic_Workflow\Database;

class Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Workflows table
        $workflows_table = $wpdb->prefix . 'aw_workflows';
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
        $executions_table = $wpdb->prefix . 'aw_executions';
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
            FOREIGN KEY (workflow_id) REFERENCES {$workflows_table}(id) ON DELETE CASCADE,
            INDEX idx_workflow_id (workflow_id),
            INDEX idx_status (status),
            INDEX idx_started_at (started_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Node logs table
        $node_logs_table = $wpdb->prefix . 'aw_node_logs';
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
            FOREIGN KEY (execution_id) REFERENCES {$executions_table}(id) ON DELETE CASCADE,
            INDEX idx_execution_id (execution_id),
            INDEX idx_node_id (node_id),
            INDEX idx_status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
}
```

---

## Step 3: Core Classes

### 3.1 Core Class

**File**: `includes/class-core.php`

```php
<?php
namespace WP_Agentic_Workflow;

use WP_Agentic_Workflow\Registry\Tools_Registry;
use WP_Agentic_Workflow\Registry\Agents_Registry;
use WP_Agentic_Workflow\Registry\Triggers_Registry;
use WP_Agentic_Workflow\Flow_Manager;
use WP_Agentic_Workflow\Executor;
use WP_Agentic_Workflow\Database\Database;

class Core {
    
    private static $instance = null;
    
    private $tools_registry;
    private $agents_registry;
    private $triggers_registry;
    private $flow_manager;
    private $executor;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Initialize registries
        $this->tools_registry = new Tools_Registry();
        $this->agents_registry = new Agents_Registry();
        $this->triggers_registry = new Triggers_Registry();
        
        // Initialize managers
        $this->flow_manager = new Flow_Manager();
        $this->executor = new Executor($this->tools_registry, $this->agents_registry);
        
        // Load configurations
        $this->load_configurations();
        
        // Initialize admin
        if (is_admin()) {
            new Admin();
        }
        
        // Hook into WordPress
        $this->init_hooks();
    }
    
    private function load_configurations() {
        $config_loader = new Config\Config_Loader();
        $config_loader->load_all();
    }
    
    private function init_hooks() {
        // Register triggers
        add_action('comment_post', [$this, 'handle_comment_post'], 10, 2);
    }
    
    public function handle_comment_post($comment_id, $comment_approved) {
        $comment = get_comment($comment_id);
        if (!$comment) {
            return;
        }
        
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
    
    public function activate() {
        Database::create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    // Getters
    public function get_tools_registry() {
        return $this->tools_registry;
    }
    
    public function get_agents_registry() {
        return $this->agents_registry;
    }
    
    public function get_triggers_registry() {
        return $this->triggers_registry;
    }
    
    public function get_flow_manager() {
        return $this->flow_manager;
    }
    
    public function get_executor() {
        return $this->executor;
    }
}
```

---

## Step 4: Comment Trigger

### 4.1 Trigger Configuration

**File**: `configs/triggers/wordpress-comment.json`

```json
{
  "id": "wordpress_comment_post",
  "name": "Comment Posted",
  "description": "Triggers when a comment is posted on a WordPress post",
  "version": "1.0.0",
  "category": "wordpress",
  "icon": "comment",
  "outputs": {
    "comment_id": {
      "type": "integer",
      "label": "Comment ID"
    },
    "post_id": {
      "type": "integer",
      "label": "Post ID"
    },
    "author": {
      "type": "string",
      "label": "Comment Author"
    },
    "content": {
      "type": "string",
      "label": "Comment Content"
    },
    "email": {
      "type": "string",
      "label": "Author Email"
    }
  },
  "handler": "WP_Agentic_Workflow\\Triggers\\WordPress\\Comment_Trigger"
}
```

### 4.2 Trigger Handler

**File**: `includes/triggers/wordpress/class-comment-trigger.php`

```php
<?php
namespace WP_Agentic_Workflow\Triggers\WordPress;

use WP_Agentic_Workflow\Triggers\Trigger_Base;

class Comment_Trigger extends Trigger_Base {
    
    public function register() {
        // Already registered in Core class
    }
    
    public function get_output_schema() {
        return [
            'comment_id' => 'integer',
            'post_id' => 'integer',
            'author' => 'string',
            'content' => 'string',
            'email' => 'string',
        ];
    }
}
```

---

## Step 5: AI Agent (LLM Decision Agent)

### 5.1 OpenAI Integration Config

**File**: `configs/integrations/openai.json`

```json
{
  "id": "openai",
  "name": "OpenAI",
  "description": "OpenAI ChatGPT integration",
  "version": "1.0.0",
  "icon": "openai",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "api_base_url": "https://api.openai.com/v1",
  "endpoints": {
    "chat": "/chat/completions"
  },
  "settings": {
    "default_model": "gpt-3.5-turbo",
    "default_temperature": 0.7,
    "default_max_tokens": 1000,
    "timeout": 30
  }
}
```

### 5.2 LLM Provider Base

**File**: `includes/llm/class-llm-provider-base.php`

```php
<?php
namespace WP_Agentic_Workflow\LLM;

abstract class LLM_Provider_Base {
    
    protected $config;
    protected $api_key;
    
    public function __construct($config) {
        $this->config = $config;
        $this->api_key = $this->get_api_key();
    }
    
    abstract public function chat($messages, $options = []);
    
    protected function get_api_key() {
        // Get from WordPress options (encrypted)
        $integration_id = $this->config['id'];
        return get_option("aw_{$integration_id}_api_key", '');
    }
}
```

### 5.3 OpenAI Provider

**File**: `includes/llm/providers/openai/class-openai-provider.php`

```php
<?php
namespace WP_Agentic_Workflow\LLM\Providers\OpenAI;

use WP_Agentic_Workflow\LLM\LLM_Provider_Base;

class OpenAI_Provider extends LLM_Provider_Base {
    
    protected $base_url = 'https://api.openai.com/v1';
    
    public function chat($messages, $options = []) {
        $model = $options['model'] ?? $this->config['settings']['default_model'];
        $temperature = $options['temperature'] ?? $this->config['settings']['default_temperature'];
        $max_tokens = $options['max_tokens'] ?? $this->config['settings']['default_max_tokens'];
        
        $response = wp_remote_post($this->base_url . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $max_tokens
            ]),
            'timeout' => $this->config['settings']['timeout']
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('OpenAI API error: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return [
            'response' => $body['choices'][0]['message']['content'],
            'model_used' => $model,
            'tokens_used' => $body['usage']['total_tokens'] ?? 0,
        ];
    }
}
```

### 5.4 LLM Decision Agent Config

**File**: `configs/agents/llm-decision-agent.json`

```json
{
  "id": "llm_decision_agent",
  "name": "LLM Decision Agent",
  "description": "Makes decisions using Large Language Models",
  "version": "1.0.0",
  "category": "ai",
  "icon": "brain",
  "inputs": {
    "prompt": {
      "type": "string",
      "required": true,
      "label": "Prompt",
      "description": "The prompt for the LLM"
    },
    "context": {
      "type": "object",
      "required": false,
      "label": "Context",
      "description": "Additional context for the agent"
    }
  },
  "outputs": {
    "decision": {
      "type": "string",
      "label": "Decision"
    },
    "reasoning": {
      "type": "string",
      "label": "Reasoning"
    }
  },
  "handler": "WP_Agentic_Workflow\\Agents\\Built_In\\LLM_Decision_Agent\\LLM_Decision_Agent",
  "settings": {
    "provider": "openai",
    "model": "gpt-3.5-turbo",
    "temperature": 0.7
  }
}
```

### 5.5 LLM Decision Agent Implementation

**File**: `includes/agents/built-in/llm-decision-agent/class-llm-decision-agent.php`

```php
<?php
namespace WP_Agentic_Workflow\Agents\Built_In\LLM_Decision_Agent;

use WP_Agentic_Workflow\Agents\Agent_Base;
use WP_Agentic_Workflow\LLM\Providers\OpenAI\OpenAI_Provider;

class LLM_Decision_Agent extends Agent_Base {
    
    protected $llm_provider;
    
    public function __construct($config) {
        parent::__construct($config);
        $this->init_llm_provider();
    }
    
    private function init_llm_provider() {
        $provider_id = $this->config['settings']['provider'] ?? 'openai';
        $integration_config = $this->load_integration_config($provider_id);
        $this->llm_provider = new OpenAI_Provider($integration_config);
    }
    
    public function execute($inputs) {
        $prompt = $inputs['prompt'] ?? '';
        $context = $inputs['context'] ?? [];
        
        // Build messages for LLM
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a decision-making assistant. Analyze the given information and make a decision. Respond with a JSON object containing "decision" and "reasoning" fields.'
            ],
            [
                'role' => 'user',
                'content' => $prompt . "\n\nContext: " . json_encode($context, JSON_PRETTY_PRINT)
            ]
        ];
        
        // Call LLM
        $response = $this->llm_provider->chat($messages, [
            'model' => $this->config['settings']['model'] ?? 'gpt-3.5-turbo',
            'temperature' => $this->config['settings']['temperature'] ?? 0.7,
        ]);
        
        // Parse response
        $decision_data = json_decode($response['response'], true);
        
        if (!$decision_data) {
            // Fallback: try to extract decision from text
            $decision_data = $this->parse_decision_from_text($response['response']);
        }
        
        return [
            'decision' => $decision_data['decision'] ?? 'unknown',
            'reasoning' => $decision_data['reasoning'] ?? $response['response'],
            'model_used' => $response['model_used'],
            'tokens_used' => $response['tokens_used'],
        ];
    }
    
    private function parse_decision_from_text($text) {
        // Simple parsing fallback
        $text_lower = strtolower($text);
        if (strpos($text_lower, 'approve') !== false) {
            return ['decision' => 'approve', 'reasoning' => $text];
        } elseif (strpos($text_lower, 'spam') !== false) {
            return ['decision' => 'spam', 'reasoning' => $text];
        } elseif (strpos($text_lower, 'delete') !== false) {
            return ['decision' => 'delete', 'reasoning' => $text];
        }
        return ['decision' => 'approve', 'reasoning' => $text];
    }
    
    private function load_integration_config($integration_id) {
        $config_file = AW_PLUGIN_DIR . "configs/integrations/{$integration_id}.json";
        if (file_exists($config_file)) {
            return json_decode(file_get_contents($config_file), true);
        }
        return [];
    }
}
```

---

## Step 6: Action Tools

### 6.1 Approve Comment Tool Config

**File**: `configs/tools/wordpress-approve-comment.json`

```json
{
  "id": "wordpress_approve_comment",
  "name": "Approve Comment",
  "description": "Approves a WordPress comment",
  "version": "1.0.0",
  "category": "wordpress",
  "icon": "check",
  "inputs": {
    "comment_id": {
      "type": "integer",
      "required": true,
      "label": "Comment ID"
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    }
  },
  "handler": "WP_Agentic_Workflow\\Tools\\Built_In\\WordPress\\Approve_Comment",
  "permissions": ["moderate_comments"]
}
```

### 6.2 Approve Comment Tool Implementation

**File**: `includes/tools/built-in/wordpress/class-approve-comment.php`

```php
<?php
namespace WP_Agentic_Workflow\Tools\Built_In\WordPress;

use WP_Agentic_Workflow\Tools\Tool_Base;

class Approve_Comment extends Tool_Base {
    
    public function execute($inputs) {
        $comment_id = $inputs['comment_id'] ?? 0;
        
        if (!$comment_id) {
            throw new \Exception('Comment ID is required');
        }
        
        // Check permissions
        if (!current_user_can('moderate_comments')) {
            throw new \Exception('Insufficient permissions');
        }
        
        // Approve comment
        $result = wp_set_comment_status($comment_id, 'approve');
        
        return [
            'success' => $result !== false,
            'comment_id' => $comment_id,
        ];
    }
}
```

### 6.3 Similar Tools

Create similar implementations for:
- `class-spam-comment.php` (uses `wp_set_comment_status($comment_id, 'spam')`)
- `class-delete-comment.php` (uses `wp_delete_comment($comment_id)`)
- `class-send-email.php` (uses `wp_mail()`)

---

## Step 7: Flow Manager & Executor

### 7.1 Flow Manager

**File**: `includes/class-flow-manager.php`

```php
<?php
namespace WP_Agentic_Workflow;

use WP_Agentic_Workflow\Executor;

class Flow_Manager {
    
    private $executor;
    
    public function __construct() {
        $this->executor = new Executor();
    }
    
    public function create_workflow($data) {
        global $wpdb;
        
        $workflow_data = [
            'nodes' => $data['nodes'] ?? [],
            'edges' => $data['edges'] ?? [],
            'settings' => $data['settings'] ?? [],
        ];
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'aw_workflows',
            [
                'title' => $data['title'] ?? 'Untitled Workflow',
                'description' => $data['description'] ?? '',
                'status' => $data['status'] ?? 'draft',
                'workflow_data' => json_encode($workflow_data),
                'created_by' => get_current_user_id(),
            ]
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public function get_workflow($workflow_id) {
        global $wpdb;
        
        $workflow = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aw_workflows WHERE id = %d",
            $workflow_id
        ));
        
        if ($workflow) {
            $workflow->workflow_data = json_decode($workflow->workflow_data, true);
        }
        
        return $workflow;
    }
    
    public function execute_workflow($workflow_id, $trigger_data = []) {
        $workflow = $this->get_workflow($workflow_id);
        
        if (!$workflow || $workflow->status !== 'published') {
            return false;
        }
        
        return $this->executor->execute($workflow, $trigger_data);
    }
}
```

### 7.2 Executor

**File**: `includes/class-executor.php`

```php
<?php
namespace WP_Agentic_Workflow;

class Executor {
    
    private $tools_registry;
    private $agents_registry;
    private $execution_id;
    
    public function __construct($tools_registry, $agents_registry) {
        $this->tools_registry = $tools_registry;
        $this->agents_registry = $agents_registry;
    }
    
    public function execute($workflow, $trigger_data = []) {
        global $wpdb;
        
        // Create execution record
        $execution_id = $this->create_execution($workflow->id, $trigger_data);
        $this->execution_id = $execution_id;
        
        $nodes = $workflow->workflow_data['nodes'] ?? [];
        $edges = $workflow->workflow_data['edges'] ?? [];
        
        // Build execution graph
        $graph = $this->build_graph($nodes, $edges);
        
        // Find trigger node
        $trigger_node = $this->find_trigger_node($nodes);
        if (!$trigger_node) {
            throw new \Exception('No trigger node found');
        }
        
        // Execute workflow
        $context = ['trigger_data' => $trigger_data];
        $this->execute_node($trigger_node, $context, $graph);
        
        // Update execution status
        $this->update_execution_status($execution_id, 'success');
        
        return $execution_id;
    }
    
    private function execute_node($node, $context, $graph) {
        $node_id = $node['id'];
        $node_type = $node['type'];
        
        // Log node execution start
        $this->log_node_start($node_id, $node_type, $context);
        
        try {
            $output = null;
            
            switch ($node_type) {
                case 'trigger':
                    $output = $context['trigger_data'];
                    break;
                    
                case 'agent':
                    $agent_id = $node['config']['agent_id'] ?? '';
                    $inputs = $this->resolve_inputs($node['config']['inputs'] ?? [], $context);
                    $output = $this->agents_registry->execute($agent_id, $inputs);
                    break;
                    
                case 'action':
                    $action_type = $node['config']['action_type'] ?? '';
                    if ($action_type === 'conditional') {
                        $output = $this->execute_conditional_action($node, $context);
                    } else {
                        $tool_id = $node['config']['tool_id'] ?? '';
                        $inputs = $this->resolve_inputs($node['config']['inputs'] ?? [], $context);
                        $output = $this->tools_registry->execute($tool_id, $inputs);
                    }
                    break;
            }
            
            // Store output in context
            $context[$node_id] = $output;
            
            // Log node execution success
            $this->log_node_success($node_id, $output);
            
            // Execute connected nodes
            $next_nodes = $graph[$node_id] ?? [];
            foreach ($next_nodes as $next_node) {
                $this->execute_node($next_node, $context, $graph);
            }
            
        } catch (\Exception $e) {
            $this->log_node_error($node_id, $e);
            throw $e;
        }
    }
    
    private function execute_conditional_action($node, $context) {
        $conditions = $node['config']['conditions'] ?? [];
        $previous_output = $context['agent_1'] ?? [];
        $decision = $previous_output['decision'] ?? '';
        
        foreach ($conditions as $condition) {
            if ($condition['if'] === "decision == '{$decision}'") {
                $tool_id = $condition['then'];
                $inputs = ['comment_id' => $context['trigger_data']['comment_id']];
                return $this->tools_registry->execute($tool_id, $inputs);
            }
        }
        
        return ['success' => false, 'message' => 'No matching condition'];
    }
    
    private function resolve_inputs($input_config, $context) {
        $resolved = [];
        foreach ($input_config as $key => $value) {
            // Simple template resolution: {{variable}}
            if (is_string($value) && preg_match('/\{\{(\w+)\}\}/', $value, $matches)) {
                $var_name = $matches[1];
                $resolved[$key] = $context[$var_name] ?? $value;
            } else {
                $resolved[$key] = $value;
            }
        }
        return $resolved;
    }
    
    // Helper methods for graph building, logging, etc.
    private function build_graph($nodes, $edges) {
        $graph = [];
        foreach ($edges as $edge) {
            $from = $edge['from'];
            $to = $edge['to'];
            if (!isset($graph[$from])) {
                $graph[$from] = [];
            }
            $to_node = $this->find_node_by_id($nodes, $to);
            if ($to_node) {
                $graph[$from][] = $to_node;
            }
        }
        return $graph;
    }
    
    private function find_node_by_id($nodes, $node_id) {
        foreach ($nodes as $node) {
            if ($node['id'] === $node_id) {
                return $node;
            }
        }
        return null;
    }
    
    private function find_trigger_node($nodes) {
        foreach ($nodes as $node) {
            if ($node['type'] === 'trigger') {
                return $node;
            }
        }
        return null;
    }
    
    private function create_execution($workflow_id, $trigger_data) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'aw_executions',
            [
                'workflow_id' => $workflow_id,
                'status' => 'running',
                'trigger_data' => json_encode($trigger_data),
            ]
        );
        return $wpdb->insert_id;
    }
    
    private function log_node_start($node_id, $node_type, $context) {
        // Implementation
    }
    
    private function log_node_success($node_id, $output) {
        // Implementation
    }
    
    private function log_node_error($node_id, $exception) {
        // Implementation
    }
    
    private function update_execution_status($execution_id, $status) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'aw_executions',
            ['status' => $status, 'completed_at' => current_time('mysql')],
            ['id' => $execution_id]
        );
    }
}
```

---

## Step 8: Simple Admin Interface

### 8.1 Admin Class

**File**: `admin/class-admin.php`

```php
<?php
namespace WP_Agentic_Workflow\Admin;

class Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Workflows',
            'Workflows',
            'manage_options',
            'agentic-workflow',
            [$this, 'render_workflow_list'],
            'dashicons-networking',
            30
        );
        
        add_submenu_page(
            'agentic-workflow',
            'All Workflows',
            'All Workflows',
            'manage_options',
            'agentic-workflow',
            [$this, 'render_workflow_list']
        );
        
        add_submenu_page(
            'agentic-workflow',
            'Add New',
            'Add New',
            'manage_options',
            'agentic-workflow-new',
            [$this, 'render_workflow_form']
        );
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'agentic-workflow') === false) {
            return;
        }
        
        wp_enqueue_style('aw-admin', AW_PLUGIN_URL . 'admin/assets/css/admin.css');
        wp_enqueue_script('aw-admin', AW_PLUGIN_URL . 'admin/assets/js/admin.js', ['jquery'], AW_VERSION, true);
    }
    
    public function render_workflow_list() {
        include AW_PLUGIN_DIR . 'admin/views/workflow-list.php';
    }
    
    public function render_workflow_form() {
        include AW_PLUGIN_DIR . 'admin/views/workflow-form.php';
    }
}
```

### 8.2 Workflow Form View

**File**: `admin/views/workflow-form.php`

```php
<?php
// Simple form-based workflow creation
// This will be replaced with visual builder in Phase 2

$workflow_data = [
    'nodes' => [
        [
            'id' => 'trigger_1',
            'type' => 'trigger',
            'trigger_type' => 'wordpress_comment_post',
        ],
        [
            'id' => 'agent_1',
            'type' => 'agent',
            'agent_id' => 'llm_decision_agent',
            'config' => [
                'inputs' => [
                    'prompt' => 'Analyze this comment and decide: approve, spam, or delete. Comment: {{trigger_1.content}}',
                    'context' => [
                        'author' => '{{trigger_1.author}}',
                        'email' => '{{trigger_1.email}}',
                    ],
                ],
            ],
        ],
        [
            'id' => 'action_1',
            'type' => 'action',
            'action_type' => 'conditional',
            'config' => [
                'conditions' => [
                    ['if' => "decision == 'approve'", 'then' => 'wordpress_approve_comment'],
                    ['if' => "decision == 'spam'", 'then' => 'wordpress_spam_comment'],
                    ['if' => "decision == 'delete'", 'then' => 'wordpress_delete_comment'],
                ],
            ],
        ],
    ],
    'edges' => [
        ['from' => 'trigger_1', 'to' => 'agent_1'],
        ['from' => 'agent_1', 'to' => 'action_1'],
    ],
];
?>

<div class="wrap">
    <h1>Create New Workflow</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('aw_create_workflow'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="title">Title</label></th>
                <td><input type="text" name="title" id="title" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="description">Description</label></th>
                <td><textarea name="description" id="description" class="large-text" rows="3"></textarea></td>
            </tr>
            <tr>
                <th><label for="status">Status</label></th>
                <td>
                    <select name="status" id="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" class="button button-primary" value="Create Workflow">
        </p>
    </form>
    
    <h2>Default Comment Moderation Workflow</h2>
    <p>This workflow will be created automatically. You can customize it later.</p>
</div>
```

---

## Step 9: Testing

### 9.1 Test Workflow

1. **Create Workflow**:
   - Go to WordPress Admin → Workflows → Add New
   - Fill in title: "Comment Moderation"
   - Set status to "Published"
   - Save

2. **Configure OpenAI API Key**:
   - Go to Settings → Credentials
   - Add OpenAI API key

3. **Test**:
   - Post a comment on any WordPress post
   - Check execution logs
   - Verify comment status changed based on AI decision

### 9.2 Debug Checklist

- [ ] Trigger fires when comment is posted
- [ ] AI agent receives comment data
- [ ] AI makes a decision
- [ ] Action executes based on decision
- [ ] Execution logs are recorded
- [ ] Comment status is updated

---

## Next Steps

After Phase 1 is complete:
1. Test thoroughly
2. Gather user feedback
3. Fix bugs
4. Move to Phase 2: Visual Workflow Builder

---

## Troubleshooting

### Common Issues

1. **Trigger not firing**: Check WordPress hooks are registered
2. **AI not responding**: Verify API key is set correctly
3. **Actions not executing**: Check permissions and tool registration
4. **Database errors**: Verify tables are created correctly

### Debug Mode

Enable debug logging:
```php
define('AW_DEBUG', true);
```

This will log all execution details to `wp-content/debug.log`.


