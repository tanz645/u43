# Configuration System

## Overview

All configurations follow JSON Schema validation and are stored in the `configs/` directory. The configuration system enables developers to add unlimited integrations, tools, and agents through simple JSON files.

## Configuration File Structure

### Tool Configuration Example

```json
{
  "id": "wordpress_create_post",
  "name": "Create WordPress Post",
  "description": "Creates a new WordPress post",
  "version": "1.0.0",
  "category": "wordpress",
  "icon": "wordpress",
  "inputs": {
    "title": {
      "type": "string",
      "required": true,
      "label": "Post Title",
      "description": "The title of the post"
    },
    "content": {
      "type": "string",
      "required": true,
      "label": "Post Content",
      "description": "The content of the post"
    },
    "status": {
      "type": "enum",
      "required": false,
      "default": "draft",
      "options": ["draft", "publish", "pending"],
      "label": "Post Status"
    }
  },
  "outputs": {
    "post_id": {
      "type": "integer",
      "label": "Post ID"
    },
    "post_url": {
      "type": "string",
      "label": "Post URL"
    }
  },
  "handler": "WordPress\\Tools\\CreatePost",
  "permissions": ["edit_posts"]
}
```

### Agent Configuration Example

```json
{
  "id": "llm_decision_agent",
  "name": "LLM Decision Agent",
  "description": "Makes decisions using Large Language Models",
  "version": "1.0.0",
  "category": "ai",
  "icon": "brain",
  "capabilities": [
    "decision_making",
    "text_analysis",
    "context_understanding"
  ],
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
    "confidence": {
      "type": "float",
      "label": "Confidence Score"
    }
  },
  "handler": "Agents\\LLMDecisionAgent",
  "settings": {
    "model": "gpt-4",
    "temperature": 0.7,
    "max_tokens": 1000
  }
}
```

### Integration Configuration Example

```json
{
  "id": "slack",
  "name": "Slack",
  "description": "Slack integration for sending messages",
  "version": "1.0.0",
  "icon": "slack",
  "authentication": {
    "type": "oauth2",
    "authorization_url": "https://slack.com/oauth/authorize",
    "token_url": "https://slack.com/api/oauth.access",
    "scopes": ["chat:write", "channels:read"]
  },
  "tools": [
    "slack_send_message",
    "slack_create_channel",
    "slack_list_channels"
  ],
  "settings": {
    "api_base_url": "https://slack.com/api"
  }
}
```

## Configuration Loading

The configuration system follows this process:

1. **Discovery**: Scans `configs/` directory recursively
2. **Validation**: Validates against JSON schemas
3. **Registration**: Registers with appropriate registry
4. **Caching**: Caches validated configurations
5. **Hot Reload**: Supports reloading in development mode

## Configuration Schema Validation

All configurations must validate against their respective schemas:
- `schemas/tool-schema.json`
- `schemas/agent-schema.json`
- `schemas/integration-schema.json`
- `schemas/trigger-schema.json`

## Adding New Configurations

### Adding a New Tool

1. Create configuration file: `configs/tools/my-tool.json`
2. Create handler class: `includes/tools/my-tool.php`
3. Implement `Tool_Base` interface
4. Configuration automatically loaded on plugin activation

### Adding a New Agent

1. Create configuration file: `configs/agents/my-agent.json`
2. Create handler class: `includes/agents/my-agent.php`
3. Implement `Agent_Base` interface
4. Define capabilities and settings

### Adding a New Integration

1. Create configuration file: `configs/integrations/my-service.json`
2. Create integration class: `includes/integrations/my-service.php`
3. Implement `Integration_Base` interface
4. Define authentication and tools
5. Define credential inputs in `inputs` section (see [Credential Management](CREDENTIALS.md))

## Auto-Discovery

The plugin automatically discovers configurations in:
- `wp-content/plugins/{plugin-name}/aw-configs/` - Plugin-specific configs
- `wp-content/uploads/aw-configs/` - User-uploaded configs
- `wp-content/themes/{theme-name}/aw-configs/` - Theme-specific configs

## AI Models Configuration

For detailed information on configuring AI models and LLM providers (ChatGPT, Claude, Gemini, DeepSeek, Grok, etc.), see [AI Models Documentation](AI_MODELS.md).

## Credential Management

For third-party integrations, users must provide credentials (API keys, connection strings, etc.) through the admin interface. See [Credential Management Documentation](CREDENTIALS.md) for:
- How credentials are collected from users
- Secure encryption and storage
- OAuth flow handling
- Multiple credential instances
- Usage in tools and handlers

