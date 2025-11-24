# U43 Plugin Structure

## Directory Structure

```
u43/
├── u43.php                          # Main plugin file
├── README.md                         # Plugin readme
├── STRUCTURE.md                      # This file
├── .gitignore                        # Git ignore rules
│
├── includes/                         # Core plugin classes
│   ├── class-autoloader.php         # PSR-4 autoloader
│   ├── class-core.php                # Main plugin class
│   ├── class-flow-manager.php        # Workflow management
│   ├── class-executor.php             # Workflow execution engine
│   │
│   ├── registry/                     # Registry system
│   │   ├── class-registry-base.php
│   │   ├── class-tools-registry.php
│   │   ├── class-agents-registry.php
│   │   └── class-triggers-registry.php
│   │
│   ├── triggers/                      # Trigger classes
│   │   ├── class-trigger-base.php
│   │   └── wordpress/
│   │       └── class-comment-trigger.php
│   │
│   ├── agents/                       # Agent classes
│   │   ├── class-agent-base.php
│   │   └── built-in/
│   │       └── llm-decision-agent/
│   │           └── class-llm-decision-agent.php
│   │
│   ├── tools/                        # Tool classes
│   │   ├── class-tool-base.php
│   │   └── built-in/
│   │       └── wordpress/
│   │           ├── class-approve-comment.php
│   │           ├── class-spam-comment.php
│   │           ├── class-delete-comment.php
│   │           └── class-send-email.php
│   │
│   ├── llm/                          # LLM provider classes
│   │   ├── class-llm-provider-base.php
│   │   └── providers/
│   │       └── openai/
│   │           └── class-openai-provider.php
│   │
│   └── config/                        # Configuration system
│       └── class-config-loader.php
│
├── admin/                            # Admin interface
│   ├── class-admin.php               # Admin class
│   ├── views/                        # Admin views
│   │   ├── workflow-list.php
│   │   ├── workflow-form.php
│   │   └── settings.php
│   └── assets/                       # Admin assets
│       ├── css/
│       │   └── admin.css
│       └── js/
│           └── admin.js
│
├── configs/                          # Configuration files (JSON)
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
│
├── database/                         # Database classes
│   └── class-database.php
│
└── public/                           # Public-facing assets (future)
    └── assets/
        ├── css/
        ├── js/
        └── images/
```

## Database Tables

The plugin creates the following database tables:

- `wp_u43_workflows` - Stores workflow definitions
- `wp_u43_executions` - Stores workflow execution records
- `wp_u43_node_logs` - Stores individual node execution logs
- `wp_u43_credentials` - Stores encrypted credentials for integrations

## Key Components

### Core Classes

- **Core**: Main plugin class, initializes all components
- **Flow_Manager**: Manages workflow CRUD operations
- **Executor**: Executes workflows step by step

### Registry System

- **Tools_Registry**: Manages and executes tools
- **Agents_Registry**: Manages and executes agents
- **Triggers_Registry**: Manages triggers and workflow execution

### Phase 1 Components

- **Comment_Trigger**: Triggers when a comment is posted
- **LLM_Decision_Agent**: Uses OpenAI to make decisions
- **WordPress Tools**: Approve, spam, delete comments, send email
- **OpenAI_Provider**: Handles OpenAI API communication

## Configuration System

All components are configured via JSON files in the `configs/` directory:

- **Triggers**: `configs/triggers/*.json`
- **Agents**: `configs/agents/*.json`
- **Tools**: `configs/tools/*.json`
- **Integrations**: `configs/integrations/*.json`

The `Config_Loader` automatically discovers and loads these configurations on plugin initialization.

## Namespace Structure

All classes use the `U43` namespace:

- `U43\Core`
- `U43\Flow_Manager`
- `U43\Registry\Tools_Registry`
- `U43\Triggers\WordPress\Comment_Trigger`
- `U43\Agents\Built_In\LLM_Decision_Agent\LLM_Decision_Agent`
- `U43\Tools\Built_In\WordPress\Approve_Comment`
- `U43\LLM\Providers\OpenAI\OpenAI_Provider`

## File Naming Convention

- PHP classes: `class-{name}.php` (e.g., `class-core.php`)
- Class names: PascalCase with underscores (e.g., `Comment_Trigger`)
- File paths: lowercase with hyphens (e.g., `class-comment-trigger.php`)

The autoloader handles the conversion automatically.

