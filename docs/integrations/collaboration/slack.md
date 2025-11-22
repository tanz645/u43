# Slack Integration

## Overview

Slack integration enables sending messages, creating channels, and receiving notifications from Slack workspaces.

## Authentication

Slack uses OAuth2 authentication. You'll need to create a Slack app and configure OAuth.

## Credential Setup

When you configure Slack integration, you'll be prompted to provide:

1. **Client ID** - Your Slack app's Client ID
2. **Client Secret** - Your Slack app's Client Secret (encrypted and stored securely)

**OAuth Flow:**
- Navigate to **Workflows → Integrations → Slack**
- Enter your Client ID and Client Secret
- Click **Connect with Slack** button
- You'll be redirected to Slack to authorize the app
- After authorization, you'll be redirected back
- Access tokens are automatically stored and refreshed

**Admin Interface:**
- Navigate to **Workflows → Integrations → Slack**
- Enter your Slack app credentials
- Click **Connect with Slack** to start OAuth flow
- Tokens are automatically managed and refreshed

See [Credential Management](../../CREDENTIALS.md) for detailed information on OAuth flows and credential storage.

## Integration Configuration

Create `configs/integrations/slack.json`:

```json
{
  "id": "slack",
  "name": "Slack",
  "description": "Slack workspace integration",
  "version": "1.0.0",
  "icon": "slack",
  "authentication": {
    "type": "oauth2",
    "authorization_url": "https://slack.com/oauth/v2/authorize",
    "token_url": "https://slack.com/api/oauth.v2.access",
    "scopes": [
      "chat:write",
      "channels:read",
      "channels:history",
      "users:read",
      "files:write"
    ]
  },
  "triggers": [
    "slack_message_received",
    "slack_channel_created",
    "slack_reaction_added"
  ],
  "tools": [
    "slack_send_message",
    "slack_create_channel",
    "slack_list_channels",
    "slack_upload_file",
    "slack_get_user_info",
    "slack_create_thread"
  ],
  "api_base_url": "https://slack.com/api"
}
```

## Tools

### Send Message Tool

Create `configs/tools/slack-send-message.json`:

```json
{
  "id": "slack_send_message",
  "name": "Send Slack Message",
  "description": "Sends a message to a Slack channel or user",
  "version": "1.0.0",
  "category": "collaboration",
  "icon": "slack",
  "integration": "slack",
  "inputs": {
    "channel": {
      "type": "string",
      "required": true,
      "label": "Channel",
      "description": "Channel ID or name (e.g., #general or C1234567890)"
    },
    "message": {
      "type": "string",
      "required": true,
      "label": "Message",
      "description": "Message text (supports Slack formatting)"
    },
    "thread_ts": {
      "type": "string",
      "required": false,
      "label": "Thread Timestamp",
      "description": "Reply to a specific message"
    },
    "blocks": {
      "type": "array",
      "required": false,
      "label": "Blocks",
      "description": "Slack Block Kit blocks for rich formatting"
    }
  },
  "outputs": {
    "ts": {
      "type": "string",
      "label": "Message Timestamp"
    },
    "channel": {
      "type": "string",
      "label": "Channel"
    }
  },
  "handler": "Integrations\\Slack\\Tools\\SendMessage"
}
```

## Example Workflows

**Form Submission Notification:**
```
Trigger: Form Submitted
  ↓
Tool: Get Form Data
  ↓
Tool: Send Slack Message → #notifications
```

**Jira Issue Alert:**
```
Trigger: Jira Issue Created
  ↓
Tool: Get Issue Details
  ↓
Tool: Send Slack Message → #engineering
```

