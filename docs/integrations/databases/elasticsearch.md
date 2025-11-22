# Elasticsearch Integration

## Overview

Elasticsearch is a distributed search and analytics engine. Perfect for full-text search, log analysis, and complex queries.

## Integration Configuration

Create `configs/integrations/elasticsearch.json`:

```json
{
  "id": "elasticsearch",
  "name": "Elasticsearch",
  "description": "Elasticsearch search and analytics integration",
  "version": "1.0.0",
  "icon": "elasticsearch",
  "authentication": {
    "type": "basic_auth",
    "description": "Username and password",
    "storage": "encrypted",
    "optional": true
  },
  "inputs": {
    "host": {
      "type": "string",
      "required": true,
      "default": "localhost",
      "label": "Host"
    },
    "port": {
      "type": "integer",
      "required": false,
      "default": 9200,
      "label": "Port"
    },
    "use_ssl": {
      "type": "boolean",
      "required": false,
      "default": false,
      "label": "Use SSL"
    }
  },
  "tools": [
    "elasticsearch_index_document",
    "elasticsearch_search",
    "elasticsearch_delete_document",
    "elasticsearch_update_document",
    "elasticsearch_bulk_index",
    "elasticsearch_create_index",
    "elasticsearch_get_mapping"
  ],
  "settings": {
    "default_index": "workflows",
    "timeout": 30
  }
}
```

## Credential Setup

When you configure Elasticsearch integration, you'll be prompted to provide:

1. **Host** - Elasticsearch server hostname (default: localhost)
2. **Port** - Elasticsearch server port (default: 9200)
3. **Use SSL** - Whether to use SSL/TLS (default: false)
4. **Username** - Elasticsearch username (if authentication required)
5. **Password** - Elasticsearch password (if authentication required, encrypted and stored securely)

**Admin Interface:**
- Navigate to **Workflows → Integrations → Elasticsearch**
- Enter your Elasticsearch connection details
- If your Elasticsearch instance requires authentication, enter username and password
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Tools

### Index Document Tool

Create `configs/tools/elasticsearch-index-document.json`:

```json
{
  "id": "elasticsearch_index_document",
  "name": "Index Elasticsearch Document",
  "description": "Indexes a document in Elasticsearch",
  "version": "1.0.0",
  "category": "database",
  "icon": "elasticsearch",
  "integration": "elasticsearch",
  "inputs": {
    "index": {
      "type": "string",
      "required": true,
      "label": "Index Name"
    },
    "document": {
      "type": "object",
      "required": true,
      "label": "Document",
      "description": "Document to index"
    },
    "document_id": {
      "type": "string",
      "required": false,
      "label": "Document ID",
      "description": "Optional document ID"
    }
  },
  "outputs": {
    "document_id": {
      "type": "string",
      "label": "Document ID"
    },
    "result": {
      "type": "string",
      "label": "Result",
      "description": "Indexing result (created/updated)"
    }
  },
  "handler": "Integrations\\Elasticsearch\\Tools\\IndexDocument"
}
```

### Search Tool

Create `configs/tools/elasticsearch-search.json`:

```json
{
  "id": "elasticsearch_search",
  "name": "Elasticsearch Search",
  "description": "Searches documents in Elasticsearch",
  "version": "1.0.0",
  "category": "database",
  "icon": "elasticsearch",
  "integration": "elasticsearch",
  "inputs": {
    "index": {
      "type": "string",
      "required": true,
      "label": "Index Name"
    },
    "query": {
      "type": "object",
      "required": true,
      "label": "Query",
      "description": "Elasticsearch query DSL"
    },
    "size": {
      "type": "integer",
      "required": false,
      "default": 10,
      "label": "Size",
      "description": "Number of results"
    },
    "from": {
      "type": "integer",
      "required": false,
      "default": 0,
      "label": "From",
      "description": "Starting offset"
    },
    "sort": {
      "type": "array",
      "required": false,
      "label": "Sort",
      "description": "Sort criteria"
    }
  },
  "outputs": {
    "hits": {
      "type": "array",
      "label": "Hits"
    },
    "total": {
      "type": "integer",
      "label": "Total Results"
    },
    "max_score": {
      "type": "float",
      "label": "Max Score"
    }
  },
  "handler": "Integrations\\Elasticsearch\\Tools\\Search"
}
```

### Bulk Index Tool

Create `configs/tools/elasticsearch-bulk-index.json`:

```json
{
  "id": "elasticsearch_bulk_index",
  "name": "Elasticsearch Bulk Index",
  "description": "Bulk indexes multiple documents",
  "version": "1.0.0",
  "category": "database",
  "icon": "elasticsearch",
  "integration": "elasticsearch",
  "inputs": {
    "index": {
      "type": "string",
      "required": true,
      "label": "Index Name"
    },
    "documents": {
      "type": "array",
      "required": true,
      "label": "Documents",
      "description": "Array of documents to index"
    }
  },
  "outputs": {
    "indexed_count": {
      "type": "integer",
      "label": "Indexed Count"
    },
    "errors": {
      "type": "array",
      "label": "Errors"
    }
  },
  "handler": "Integrations\\Elasticsearch\\Tools\\BulkIndex"
}
```

## Example Workflows

**Index WordPress Posts:**
```
Trigger: Post Published
  ↓
Tool: Get Post Data
  ↓
Tool: Elasticsearch Index Document
```

**Search Workflow:**
```
Trigger: User Search Query
  ↓
Tool: Elasticsearch Search → Full-text search
  ↓
Tool: Format Results
  ↓
Tool: Return Response
```

**Log Analysis:**
```
Trigger: Scheduled → Hourly
  ↓
Tool: Elasticsearch Search → Error logs
  ↓
Tool: Aggregate Results
  ↓
Tool: Send Alert if Errors Found
```


