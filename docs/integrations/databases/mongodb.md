# MongoDB Integration

## Overview

MongoDB is a NoSQL document database that stores data in flexible, JSON-like documents. Perfect for storing workflow data, logs, and unstructured data.

## Integration Configuration

Create `configs/integrations/mongodb.json`:

```json
{
  "id": "mongodb",
  "name": "MongoDB",
  "description": "MongoDB NoSQL database integration",
  "version": "1.0.0",
  "icon": "mongodb",
  "authentication": {
    "type": "connection_string",
    "description": "MongoDB connection string",
    "storage": "encrypted"
  },
  "inputs": {
    "connection_string": {
      "type": "string",
      "required": true,
      "label": "MongoDB Connection String",
      "description": "MongoDB connection string (mongodb:// or mongodb+srv://)",
      "placeholder": "mongodb://username:password@host:port/database",
      "sensitive": true
    },
    "database_name": {
      "type": "string",
      "required": true,
      "label": "Database Name",
      "description": "Name of the MongoDB database to use",
      "sensitive": false
    }
  },
  "tools": [
    "mongodb_insert_document",
    "mongodb_find_documents",
    "mongodb_update_document",
    "mongodb_delete_document",
    "mongodb_aggregate",
    "mongodb_create_index",
    "mongodb_list_collections"
  ],
  "settings": {
    "default_timeout": 30,
    "connection_pool_size": 10
  }
}
```

## Tools

### Insert Document Tool

Create `configs/tools/mongodb-insert-document.json`:

```json
{
  "id": "mongodb_insert_document",
  "name": "Insert MongoDB Document",
  "description": "Inserts a document into a MongoDB collection",
  "version": "1.0.0",
  "category": "database",
  "icon": "mongodb",
  "integration": "mongodb",
  "inputs": {
    "collection": {
      "type": "string",
      "required": true,
      "label": "Collection Name"
    },
    "document": {
      "type": "object",
      "required": true,
      "label": "Document",
      "description": "Document to insert (JSON object)"
    }
  },
  "outputs": {
    "inserted_id": {
      "type": "string",
      "label": "Inserted ID"
    },
    "acknowledged": {
      "type": "boolean",
      "label": "Acknowledged"
    }
  },
  "handler": "Integrations\\MongoDB\\Tools\\InsertDocument"
}
```

### Find Documents Tool

Create `configs/tools/mongodb-find-documents.json`:

```json
{
  "id": "mongodb_find_documents",
  "name": "Find MongoDB Documents",
  "description": "Queries documents from a MongoDB collection",
  "version": "1.0.0",
  "category": "database",
  "icon": "mongodb",
  "integration": "mongodb",
  "inputs": {
    "collection": {
      "type": "string",
      "required": true,
      "label": "Collection Name"
    },
    "filter": {
      "type": "object",
      "required": false,
      "label": "Filter",
      "description": "Query filter (MongoDB query syntax)"
    },
    "projection": {
      "type": "object",
      "required": false,
      "label": "Projection",
      "description": "Fields to return"
    },
    "sort": {
      "type": "object",
      "required": false,
      "label": "Sort",
      "description": "Sort criteria"
    },
    "limit": {
      "type": "integer",
      "required": false,
      "default": 100,
      "label": "Limit"
    },
    "skip": {
      "type": "integer",
      "required": false,
      "default": 0,
      "label": "Skip"
    }
  },
  "outputs": {
    "documents": {
      "type": "array",
      "label": "Documents"
    },
    "count": {
      "type": "integer",
      "label": "Count"
    }
  },
  "handler": "Integrations\\MongoDB\\Tools\\FindDocuments"
}
```

### Aggregate Tool

Create `configs/tools/mongodb-aggregate.json`:

```json
{
  "id": "mongodb_aggregate",
  "name": "MongoDB Aggregate",
  "description": "Performs aggregation pipeline operations",
  "version": "1.0.0",
  "category": "database",
  "icon": "mongodb",
  "integration": "mongodb",
  "inputs": {
    "collection": {
      "type": "string",
      "required": true,
      "label": "Collection Name"
    },
    "pipeline": {
      "type": "array",
      "required": true,
      "label": "Aggregation Pipeline",
      "description": "Array of pipeline stages"
    }
  },
  "outputs": {
    "results": {
      "type": "array",
      "label": "Results"
    }
  },
  "handler": "Integrations\\MongoDB\\Tools\\Aggregate"
}
```

## Credential Setup

When you configure MongoDB integration, you'll be prompted to provide:

1. **MongoDB Connection String** - Your MongoDB connection URI
   - Format: `mongodb://username:password@host:port/database`
   - Or: `mongodb+srv://username:password@cluster.mongodb.net/database` (for Atlas)
   - This field is encrypted and stored securely

2. **Database Name** - The database to use for operations

**Admin Interface:**
- Navigate to **Workflows → Integrations → MongoDB**
- Enter your MongoDB credentials
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Example Workflows

**Store Workflow Execution Logs:**
```
Trigger: Workflow Executed
  ↓
Tool: Get Execution Data
  ↓
Tool: Insert MongoDB Document → "workflow_logs"
```

**Query Workflow History:**
```
Trigger: User Request → "Get Workflow History"
  ↓
Tool: Find MongoDB Documents → Filter by user_id
  ↓
Tool: Format Results
  ↓
Tool: Return Response
```

**Data Aggregation:**
```
Trigger: Scheduled → Daily Report
  ↓
Tool: MongoDB Aggregate → Calculate metrics
  ↓
Tool: Generate Report
  ↓
Tool: Send Email Report
```


