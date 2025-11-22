# W3 Total Cache Integration

## Overview

W3 Total Cache integration enables workflows to manage caching operations.

> **Note**: W3 Total Cache is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure W3 Total Cache is installed and activated.

## Integration Configuration

Create `configs/integrations/w3-total-cache.json`:

```json
{
  "id": "w3_total_cache",
  "name": "W3 Total Cache",
  "description": "W3 Total Cache integration",
  "version": "1.0.0",
  "icon": "cache",
  "plugin_dependency": {
    "plugin": "w3-total-cache/w3-total-cache.php",
    "min_version": "2.0.0"
  },
  "tools": [
    "w3tc_flush_cache",
    "w3tc_flush_page_cache",
    "w3tc_flush_object_cache",
    "w3tc_flush_database_cache"
  ],
  "settings": {
    "auto_flush": false
  }
}
```

## Example Workflows

**Auto Cache Clear:**
```
Trigger: Post Updated
  ↓
Tool: W3TC Flush Page Cache
  ↓
Tool: W3TC Flush Object Cache
```

