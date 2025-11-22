# SQLite Integration

## Overview

SQLite is a lightweight, file-based database perfect for local storage, testing, and small-scale applications.

## Integration Configuration

Create `configs/integrations/sqlite.json`:

```json
{
  "id": "sqlite",
  "name": "SQLite",
  "description": "SQLite file-based database integration",
  "version": "1.0.0",
  "icon": "sqlite",
  "authentication": {
    "type": "none"
  },
  "inputs": {
    "database_path": {
      "type": "string",
      "required": true,
      "label": "Database Path",
      "description": "Path to SQLite database file"
    }
  },
  "tools": [
    "sqlite_query",
    "sqlite_insert",
    "sqlite_update",
    "sqlite_delete",
    "sqlite_create_table",
    "sqlite_backup"
  ],
  "settings": {
    "default_timeout": 30,
    "enable_wal": true
  }
}
```

## Credential Setup

SQLite doesn't require credentials - it's a file-based database. You only need to provide:

1. **Database Path** - Path to the SQLite database file
   - Can be absolute or relative to WordPress root
   - Example: `/path/to/database.db` or `wp-content/uploads/my-db.db`

**Admin Interface:**
- Navigate to **Workflows → Integrations → SQLite**
- Enter the path to your SQLite database file
- Click **Test Connection** to verify file access
- Click **Save Settings** to store

> **Note**: SQLite doesn't use encrypted credentials since it only requires a file path. However, ensure the file path is stored securely and has proper file permissions.

## Use Cases

- Local development and testing
- Small-scale data storage
- Offline workflows
- Backup and export operations

## Example Workflows

**Local Data Storage:**
```
Trigger: Workflow Executed
  ↓
Tool: SQLite Insert → Local log
```

**Data Export:**
```
Trigger: Scheduled → Weekly Export
  ↓
Tool: SQLite Backup → Export file
  ↓
Tool: Upload to Cloud Storage
```


