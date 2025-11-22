# UpdraftPlus Integration

## Overview

UpdraftPlus integration enables automated backup operations and restore workflows.

> **Note**: UpdraftPlus is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure UpdraftPlus is installed and activated. (Note: UpdraftPlus may have its own cloud storage credentials, but those are managed within UpdraftPlus itself, not in our plugin.)

## Integration Configuration

Create `configs/integrations/updraftplus.json`:

```json
{
  "id": "updraftplus",
  "name": "UpdraftPlus",
  "description": "UpdraftPlus backup integration",
  "version": "1.0.0",
  "icon": "backup",
  "plugin_dependency": {
    "plugin": "updraftplus/updraftplus.php",
    "min_version": "1.0.0"
  },
  "triggers": [
    "updraftplus_backup_completed",
    "updraftplus_backup_failed",
    "updraftplus_restore_completed"
  ],
  "tools": [
    "updraftplus_create_backup",
    "updraftplus_restore_backup",
    "updraftplus_list_backups",
    "updraftplus_delete_backup"
  ],
  "settings": {
    "auto_backup": false
  }
}
```

## Example Workflows

**Scheduled Backup:**
```
Trigger: Scheduled → Daily 2 AM
  ↓
Tool: UpdraftPlus Create Backup
  ↓
Tool: Upload to Cloud Storage
  ↓
Tool: Send Backup Confirmation Email
```

