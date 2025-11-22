# Pretty Links Integration

## Overview

Pretty Links integration enables managing short links and affiliate links through workflows.

> **Note**: Pretty Links is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure Pretty Links is installed and activated.

## Integration Configuration

Create `configs/integrations/pretty-links.json`:

```json
{
  "id": "pretty_links",
  "name": "Pretty Links",
  "description": "Pretty Links integration",
  "version": "1.0.0",
  "icon": "link",
  "plugin_dependency": {
    "plugin": "pretty-link/pretty-link.php",
    "min_version": "3.0.0"
  },
  "triggers": [
    "pretty_link_clicked",
    "pretty_link_created"
  ],
  "tools": [
    "pretty_link_create",
    "pretty_link_get_clicks",
    "pretty_link_update"
  ]
}
```

## Example Workflows

**Link Tracking:**
```
Trigger: Pretty Link Clicked
  ↓
Tool: Get Click Data
  ↓
Tool: Log to Analytics
  ↓
Tool: Update Click Count
```

