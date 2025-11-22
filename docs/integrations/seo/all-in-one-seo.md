# All in One SEO Integration

## Overview

All in One SEO (AIOSEO) integration enables workflows to interact with SEO metadata, sitemaps, and schema markup.

> **Note**: All in One SEO is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure All in One SEO is installed and activated.

## Integration Configuration

Create `configs/integrations/all-in-one-seo.json`:

```json
{
  "id": "all_in_one_seo",
  "name": "All in One SEO",
  "description": "All in One SEO pack integration",
  "version": "1.0.0",
  "icon": "seo",
  "plugin_dependency": {
    "plugin": "all-in-one-seo-pack/all_in_one_seo_pack.php",
    "min_version": "4.0.0"
  },
  "triggers": [
    "aioseo_meta_updated",
    "aioseo_sitemap_generated",
    "aioseo_schema_generated"
  ],
  "tools": [
    "aioseo_get_meta",
    "aioseo_update_meta",
    "aioseo_generate_sitemap",
    "aioseo_get_schema",
    "aioseo_analyze_content"
  ],
  "settings": {
    "auto_sitemap": true
  }
}
```

## Tools

### Get SEO Meta Tool

Create `configs/tools/aioseo-get-meta.json`:

```json
{
  "id": "aioseo_get_meta",
  "name": "Get SEO Meta",
  "description": "Retrieves SEO metadata for a post/page",
  "version": "1.0.0",
  "category": "seo",
  "icon": "seo",
  "integration": "all_in_one_seo",
  "inputs": {
    "post_id": {
      "type": "integer",
      "required": true,
      "label": "Post ID"
    }
  },
  "outputs": {
    "title": {
      "type": "string",
      "label": "SEO Title"
    },
    "description": {
      "type": "string",
      "label": "Meta Description"
    },
    "keywords": {
      "type": "array",
      "label": "Keywords"
    },
    "og_image": {
      "type": "string",
      "label": "OG Image URL"
    }
  },
  "handler": "Integrations\\AIOSEO\\Tools\\GetMeta"
}
```

### Update SEO Meta Tool

Create `configs/tools/aioseo-update-meta.json`:

```json
{
  "id": "aioseo_update_meta",
  "name": "Update SEO Meta",
  "description": "Updates SEO metadata for a post/page",
  "version": "1.0.0",
  "category": "seo",
  "icon": "seo",
  "integration": "all_in_one_seo",
  "inputs": {
    "post_id": {
      "type": "integer",
      "required": true,
      "label": "Post ID"
    },
    "title": {
      "type": "string",
      "required": false,
      "label": "SEO Title"
    },
    "description": {
      "type": "string",
      "required": false,
      "label": "Meta Description"
    },
    "keywords": {
      "type": "array",
      "required": false,
      "label": "Keywords"
    },
    "og_image": {
      "type": "string",
      "required": false,
      "label": "OG Image URL"
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    }
  },
  "handler": "Integrations\\AIOSEO\\Tools\\UpdateMeta"
}
```

## Example Workflows

**Auto-SEO Optimization:**
```
Trigger: Post Published
  ↓
Agent: Generate SEO Meta (LLM Agent)
  ↓
Tool: Update SEO Meta
  ↓
Tool: Generate Sitemap
```

