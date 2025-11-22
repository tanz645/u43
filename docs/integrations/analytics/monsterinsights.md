# MonsterInsights Integration

## Overview

MonsterInsights Lite provides Google Analytics integration. Similar to Site Kit but with different API access.

## Integration Configuration

Create `configs/integrations/monsterinsights.json`:

```json
{
  "id": "monsterinsights",
  "name": "MonsterInsights",
  "description": "MonsterInsights Google Analytics integration",
  "version": "1.0.0",
  "icon": "analytics",
  "plugin_dependency": {
    "plugin": "google-analytics-for-wordpress/googleanalytics.php",
    "min_version": "8.0.0"
  },
  "triggers": [
    "monsterinsights_data_synced",
    "monsterinsights_report_generated"
  ],
  "tools": [
    "monsterinsights_get_report",
    "monsterinsights_get_realtime_data",
    "monsterinsights_get_audience_data"
  ],
  "settings": {
    "api_enabled": true
  }
}
```

## Tools

Similar to Site Kit integration - see [Site Kit Integration](../analytics/site-kit.md) for reference patterns.

