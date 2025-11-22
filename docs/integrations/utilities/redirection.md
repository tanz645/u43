# Redirection Integration

## Overview

Redirection plugin integration enables managing URL redirects through workflows.

> **Note**: Redirection is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure Redirection is installed and activated.

## Integration Configuration

Create `configs/integrations/redirection.json`:

```json
{
  "id": "redirection",
  "name": "Redirection",
  "description": "Redirection plugin integration",
  "version": "1.0.0",
  "icon": "redirect",
  "plugin_dependency": {
    "plugin": "redirection/redirection.php",
    "min_version": "5.0.0"
  },
  "triggers": [
    "redirection_redirect_created",
    "redirection_404_detected"
  ],
  "tools": [
    "redirection_create_redirect",
    "redirection_get_redirects",
    "redirection_delete_redirect"
  ],
  "settings": {
    "auto_redirect_404": false
  }
}
```

## Example Workflows

**Auto 404 Redirect:**
```
Trigger: 404 Detected
  ↓
Tool: Get 404 URL
  ↓
Agent: Find Similar URL (LLM Agent)
  ↓
Tool: Create Redirect
```

