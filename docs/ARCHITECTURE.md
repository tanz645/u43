# Architecture

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Core                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Hooks      │  │   Events     │  │   Filters    │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼─────────────────┼─────────────────┼──────────────┘
          │                 │                 │
          └─────────────────┼─────────────────┘
                            │
          ┌─────────────────▼─────────────────┐
          │   Workflow Engine Core            │
          │  ┌──────────┐  ┌──────────┐      │
          │  │  Flow    │  │ Executor │      │
          │  │ Manager  │  │  Engine  │      │
          │  └────┬─────┘  └────┬─────┘      │
          └───────┼─────────────┼────────────┘
                  │             │
    ┌─────────────┼─────────────┼─────────────┐
    │             │             │             │
┌───▼───┐   ┌─────▼─────┐  ┌───▼────┐  ┌────▼────┐
│ Tools │   │  Agents   │  │Triggers │  │ Actions │
│Registry│  │ Registry  │  │Registry │  │Registry │
└───┬───┘   └─────┬─────┘  └───┬─────┘  └────┬────┘
    │             │             │             │
    └─────────────┼─────────────┼─────────────┘
                  │             │
          ┌───────▼─────────────▼───────┐
          │   Configuration System      │
          │  ┌──────────────────────┐   │
          │  │  Config Loader       │   │
          │  │  Config Validator    │   │
          │  │  Config Cache        │   │
          │  └──────────────────────┘   │
          └─────────────────────────────┘
                  │
          ┌───────▼───────────────────────┐
          │   Credential Manager          │
          │  ┌────────────────────────┐   │
          │  │  Credential Storage    │   │
          │  │  Encryption Service    │   │
          │  │  Admin UI Forms        │   │
          │  │  OAuth Flow Handler    │   │
          │  └────────────────────────┘   │
          └───────────────────────────────┘
```

## Core Components

### 1. Flow Manager
- Stores and manages workflow definitions
- Handles flow CRUD operations (Create, Read, Update, Delete)
- Validates flow structure and node connections
- Manages flow versions and history
- Handles workflow states (Draft, Published, Paused, Archived)
- Provides workflow analytics and monitoring

### 2. Executor Engine
- Executes workflows step by step (node by node)
- Manages execution context and state
- Handles data flow between nodes
- Handles error recovery and retries
- Supports synchronous and asynchronous execution
- Logs execution details for monitoring
- Tracks performance metrics

### 3. Registry System
- **Tools Registry**: Manages all available tools
- **Agents Registry**: Manages all available agents
- **Triggers Registry**: Manages workflow triggers
- **Actions Registry**: Manages workflow actions
- **Model Registry**: Manages AI/LLM model providers and configurations

### 4. Configuration System
- Loads configurations from files
- Validates configuration schemas
- Caches configurations for performance
- Supports hot-reloading in development

### 5. Credential Manager
- Securely stores user-provided credentials
- Encrypts sensitive data (API keys, passwords, tokens)
- Provides admin UI for credential input
- Handles OAuth flows for third-party services
- Manages credential validation and refresh
- Supports multiple credential instances per integration

## Extensibility

The architecture is designed to be highly extensible, allowing developers to add new components and even new node types as needed.

### Adding New Components Within Existing Categories

**Adding New Tools** (becomes Action nodes):
1. Create configuration file: `configs/tools/my-tool.json`
2. Create handler class extending `Tool_Base`
3. Auto-discovered and registered via Tools Registry

**Adding New Agents** (becomes Agent nodes):
1. Create configuration file: `configs/agents/my-agent.json`
2. Create handler class extending `Agent_Base`
3. Auto-discovered and registered via Agents Registry

**Adding New Triggers** (becomes Trigger nodes):
1. Create configuration file: `configs/triggers/my-trigger.json`
2. Create handler class extending `Trigger_Base`
3. Auto-discovered and registered via Triggers Registry

**Adding New Actions** (becomes Action nodes):
1. Create configuration file: `configs/actions/my-action.json`
2. Create handler class extending `Action_Base`
3. Auto-discovered and registered via Actions Registry

**Adding New Admin Handlers** (for integration-specific admin functionality):
1. Create handler file: `admin/handlers/class-{integration}-handler.php`
2. Follow naming pattern: `class-{integration}-handler.php`
3. Use namespace: `U43\Admin\Handlers\{Integration}_Handler`
4. Register AJAX actions in constructor
5. Auto-discovered and loaded by `Admin::load_handlers()`

Example:
```php
namespace U43\Admin\Handlers;

class Slack_Handler {
    public function __construct() {
        add_action('wp_ajax_u43_test_slack_connection', [$this, 'ajax_test_connection']);
    }
    
    public function ajax_test_connection() {
        // Handler implementation
    }
}
```

### Adding New Node Type Categories

The architecture supports adding entirely new node type categories beyond the built-in types (trigger, action, agent, condition, delay, loop, data transformation, error handling).

**To add a new node type category:**

1. **Create a new registry**:
   - Add `class-{type}-registry.php` in `includes/registry/`
   - Extend the base registry class
   - Implement registration and discovery methods

2. **Create base class**:
   - Add `class-{type}-base.php` in `includes/{type}/`
   - Define the interface/contract for the new node type
   - Implement common functionality

3. **Update Executor Engine**:
   - Add handling for the new node type in `class-executor.php`
   - Implement execution logic for the new type

4. **Update Flow Manager**:
   - Add validation rules for the new node type
   - Update node schema validation

5. **Add configuration schema**:
   - Create `schemas/{type}-schema.json` for validation
   - Define required and optional fields

6. **Update UI/Workflow Builder**:
   - Add UI components for the new node type
   - Add to node palette in workflow builder
   - Implement node configuration UI

7. **Register the new registry**:
   - Add registry initialization in `class-core.php`
   - Hook into the plugin loading process

**Example: Adding a "Notification" node type**

```php
// 1. Registry: includes/registry/class-notification-registry.php
class Notification_Registry extends Registry_Base {
    // Register notification handlers
}

// 2. Base class: includes/notifications/class-notification-base.php
abstract class Notification_Base {
    abstract public function send($data);
}

// 3. Configuration: configs/notifications/email-notification.json
{
  "id": "email_notification",
  "type": "notification",
  "name": "Email Notification",
  "handler": "Notifications\\Email"
}
```

**Hooks for Extension**:

- `aw_register_node_types`: Filter to add custom node types
- `aw_node_type_config`: Filter to modify node type configuration
- `aw_before_node_execute`: Action before node execution
- `aw_after_node_execute`: Action after node execution

### Auto-Discovery

The configuration system automatically discovers new components in:
- `wp-content/plugins/{plugin-name}/aw-configs/` - Plugin-specific configs
- `wp-content/uploads/aw-configs/` - User-uploaded configs
- `wp-content/themes/{theme-name}/aw-configs/` - Theme-specific configs

This allows third-party plugins and themes to extend the workflow system without modifying core files.

## Plugin Structure

```
wp-agentic-workflow/
├── wp-agentic-workflow.php          # Main plugin file
├── includes/
│   ├── class-core.php               # Core plugin class
│   ├── class-flow-manager.php       # Flow management
│   ├── class-executor.php           # Workflow execution engine
│   ├── registry/
│   │   ├── class-tools-registry.php
│   │   ├── class-agents-registry.php
│   │   ├── class-triggers-registry.php
│   │   └── class-actions-registry.php
│   ├── config/
│   │   ├── class-config-loader.php
│   │   ├── class-config-validator.php
│   │   └── schemas/
│   │       ├── tool-schema.json
│   │       ├── agent-schema.json
│   │       ├── trigger-schema.json
│   │       └── llm-provider-schema.json
│   ├── credentials/
│   │   ├── class-credential-manager.php
│   │   ├── class-credential-storage.php
│   │   ├── class-encryption-service.php
│   │   └── class-oauth-handler.php
│   ├── integrations/
│   │   ├── class-integration-base.php
│   │   └── built-in/                 # Built-in integrations
│   │       ├── slack/
│   │       ├── stripe/
│   │       └── ...
│   ├── agents/
│   │   ├── class-agent-base.php
│   │   └── built-in/                 # Built-in agents
│   │       ├── decision-agent/
│   │       ├── llm-agent/
│   │       └── ...
│   ├── llm/
│   │   ├── class-llm-provider-base.php
│   │   └── providers/                # LLM provider implementations
│   │       ├── openai/
│   │       ├── anthropic/
│   │       ├── google/
│   │       ├── deepseek/
│   │       └── ...
│   ├── tools/
│   │   ├── class-tool-base.php
│   │   └── built-in/                 # Built-in tools
│   │       ├── wordpress/
│   │       ├── http/
│   │       └── ...
│   └── api/
│       ├── class-rest-api.php        # REST API endpoints
│       └── endpoints/
│           ├── flows.php
│           ├── tools.php
│           └── agents.php
├── admin/
│   ├── class-admin.php              # Main admin class
│   ├── handlers/                    # Modular admin handlers (one per integration)
│   │   ├── class-whatsapp-handler.php
│   │   └── class-{integration}-handler.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── views/
│       ├── flow-builder.php
│       ├── settings.php
│       └── credentials/
│           ├── credential-form.php
│           ├── oauth-callback.php
│           └── credential-list.php
├── public/
│   ├── class-public.php
│   └── assets/
├── configs/                          # Configuration files
│   ├── tools/
│   │   ├── wordpress-post.json
│   │   ├── http-request.json
│   │   └── ...
│   ├── agents/
│   │   ├── llm-agent.json
│   │   └── ...
│   └── integrations/
│       ├── slack.json
│       ├── stripe.json
│       ├── openai.json
│       ├── anthropic.json
│       ├── google-gemini.json
│       ├── deepseek.json
│       ├── grok.json
│       └── ...
├── database/
│   ├── class-database.php
│   └── migrations/
│       ├── 001_create_flows_table.php
│       ├── 002_create_credentials_table.php
│       ├── 003_create_executions_table.php
│       ├── 004_create_execution_logs_table.php
│       └── ...
├── tests/
│   ├── unit/
│   └── integration/
└── languages/
```

