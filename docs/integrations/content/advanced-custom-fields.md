# Advanced Custom Fields (ACF) Integration

## Overview

Advanced Custom Fields integration enables workflows to read and write custom field data.

> **Note**: Advanced Custom Fields is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure ACF is installed and activated.

## Integration Configuration

Create `configs/integrations/advanced-custom-fields.json`:

```json
{
  "id": "advanced_custom_fields",
  "name": "Advanced Custom Fields",
  "description": "ACF custom fields integration",
  "version": "1.0.0",
  "icon": "acf",
  "plugin_dependency": {
    "plugin": "advanced-custom-fields/acf.php",
    "min_version": "5.0.0"
  },
  "triggers": [
    "acf_field_updated",
    "acf_field_group_saved"
  ],
  "tools": [
    "acf_get_field",
    "acf_update_field",
    "acf_get_fields",
    "acf_get_field_groups"
  ],
  "handler": "Integrations\\ACF\\ACF_Integration"
}
```

## Tools

### Get Field Tool

Create `configs/tools/acf-get-field.json`:

```json
{
  "id": "acf_get_field",
  "name": "Get ACF Field",
  "description": "Retrieves an ACF field value",
  "version": "1.0.0",
  "category": "content",
  "icon": "acf",
  "integration": "advanced_custom_fields",
  "inputs": {
    "post_id": {
      "type": "integer",
      "required": true,
      "label": "Post ID"
    },
    "field_name": {
      "type": "string",
      "required": true,
      "label": "Field Name"
    }
  },
  "outputs": {
    "value": {
      "type": "mixed",
      "label": "Field Value"
    }
  },
  "handler": "Integrations\\ACF\\Tools\\GetField"
}
```

## Example Workflows

**Custom Field Automation:**
```
Trigger: Post Published
  ↓
Tool: Get ACF Field → "custom_data"
  ↓
Tool: Process Custom Data
  ↓
Tool: Update ACF Field → "processed_data"
```

