# WordPress Agentic Workflow Plugin

A powerful, extensible workflow automation plugin for WordPress that enables users to create complex workflows using flows, tools, and AI agents. Inspired by n8n, Activepieces, Ottokit, Node-RED, and Retool.

## Quick Start

This plugin provides a visual workflow builder that allows WordPress users to:
- Create automated workflows using a drag-and-drop interface
- Integrate with external services and APIs
- Leverage AI agents for intelligent automation
- Connect WordPress hooks and events to workflows
- Extend functionality through configuration files

## Core Concepts

### Flow
A flow is a visual representation of a workflow consisting of nodes connected by edges. Each flow defines:
- **Nodes**: Individual steps in the workflow (triggers, actions, agents)
- **Edges**: Connections between nodes defining data flow
- **Execution Context**: Runtime state and data passing between nodes

### Tools
Tools are reusable functions that perform specific actions:
- **Built-in Tools**: WordPress-specific actions (create post, send email, etc.)
- **Integration Tools**: External service integrations (Slack, Stripe, etc.)
- **Custom Tools**: Developer-defined tools via configuration

### Agents
AI-powered agents that can:
- Make decisions based on context
- Process natural language inputs
- Execute complex multi-step operations
- Learn from workflow execution patterns

## Documentation

- **[Architecture](docs/ARCHITECTURE.md)** - System architecture and plugin structure
- **[Workflows & Nodes](docs/WORKFLOWS.md)** - Understanding workflows, node types, and workflow management
- **[Database Schema](docs/DATABASE_SCHEMA.md)** - Database structure for workflows, executions, and logs (includes UML diagrams)
- **[Configuration System](docs/CONFIGURATION.md)** - How to configure tools, agents, and integrations
- **[Credential Management](docs/CREDENTIALS.md)** - Secure credential storage and management for third-party integrations
- **[AI Models](docs/AI_MODELS.md)** - Configure ChatGPT, Claude, Gemini, DeepSeek, Grok, and other LLM providers
- **[RAG & Vector Stores](docs/RAG_VECTOR_STORES.md)** - Retrieval-Augmented Generation and vector database integrations
- **[Integrations](docs/INTEGRATIONS.md)** - WordPress integration and third-party plugin examples ([Browse all integrations](docs/integrations/))
- **[API Reference](docs/API.md)** - REST API and PHP API documentation
- **[Development Guide](docs/DEVELOPMENT.md)** - Setup, testing, and contribution guidelines

## Features

- ✅ **Configuration-Based Extensibility** - Add unlimited integrations via JSON configs
- ✅ **WordPress Hook Integration** - Trigger workflows from WordPress hooks and events
- ✅ **Visual Workflow Builder** - Drag-and-drop interface for creating workflows
- ✅ **AI Agents** - Intelligent decision-making and automation with multiple LLM providers
- ✅ **Multiple AI Models** - Support for ChatGPT, Claude, Gemini, DeepSeek, Grok, and more
- ✅ **RAG Support** - Retrieval-Augmented Generation for context-aware AI responses
- ✅ **Vector Stores** - Integration with Pinecone, Weaviate, Qdrant, Chroma, and more
- ✅ **Third-Party Plugin Support** - Seamless integration with WooCommerce, Contact Form 7, WPForms, Site Kit, and more
- ✅ **Project Management Tools** - Integrate with Jira, Trello, ClickUp, Monday.com, Asana
- ✅ **Collaboration Tools** - Connect with Slack, Microsoft Teams, and more
- ✅ **Database Integrations** - PostgreSQL, MySQL, MongoDB, ClickHouse, TimescaleDB, Redis, Elasticsearch, SQLite
- ✅ **OAuth Authentication** - Support for OAuth2 integrations
- ✅ **Secure Credential Storage** - Encrypted storage for API keys, passwords, and tokens
- ✅ **REST API** - Full API access for programmatic control

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Composer (for dependencies)
- Node.js & npm (for frontend assets)

## Installation

1. Clone the repository
2. Run `composer install`
3. Run `npm install`
4. Activate the plugin in WordPress admin

## Quick Example

```php
// Execute a tool programmatically
$workflow = WP_Agentic_Workflow();
$result = $workflow->get_tools_registry()->execute('wordpress_create_post', [
    'title' => 'Hello World',
    'content' => 'This is a test post'
]);

// Trigger a workflow
$workflow->get_flow_manager()->execute_flow('flow_id', [
    'trigger_data' => $data
]);
```

## License

GPL v2 or later

## Support

For support, please open an issue on GitHub or contact the development team.
