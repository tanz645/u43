# Sucuri Integration

## Overview

Sucuri security integration enables workflows to respond to security events and monitor threats.

> **Note**: Sucuri is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure Sucuri is installed and activated.

## Integration Configuration

Create `configs/integrations/sucuri.json`:

```json
{
  "id": "sucuri",
  "name": "Sucuri",
  "description": "Sucuri security integration",
  "version": "1.0.0",
  "icon": "security",
  "plugin_dependency": {
    "plugin": "sucuri-scanner/sucuri.php",
    "min_version": "1.8.0"
  },
  "triggers": [
    "sucuri_threat_detected",
    "sucuri_scan_completed",
    "sucuri_firewall_blocked"
  ],
  "tools": [
    "sucuri_run_scan",
    "sucuri_get_security_logs",
    "sucuri_block_ip",
    "sucuri_whitelist_ip"
  ],
  "settings": {
    "auto_scan": false
  }
}
```

## Example Workflows

**Security Alert:**
```
Trigger: Threat Detected
  ↓
Tool: Get Threat Details
  ↓
Tool: Send Slack Alert
  ↓
Tool: Block IP Address
```

