# Architecture

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Core                            │
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
│   ├── class-admin.php
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

