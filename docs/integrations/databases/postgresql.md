# PostgreSQL Integration

## Overview

PostgreSQL is an advanced open-source relational database with support for JSON, arrays, and vector operations (via pgvector extension).

## Integration Configuration

Create `configs/integrations/postgresql.json`:

```json
{
  "id": "postgresql",
  "name": "PostgreSQL",
  "description": "PostgreSQL relational database integration",
  "version": "1.0.0",
  "icon": "postgresql",
  "authentication": {
    "type": "connection_string",
    "description": "PostgreSQL connection string",
    "storage": "encrypted"
  },
  "inputs": {
    "connection_string": {
      "type": "string",
      "required": true,
      "label": "Connection String",
      "description": "PostgreSQL connection string (postgresql://user:pass@host:port/db)"
    }
  },
  "tools": [
    "postgresql_query",
    "postgresql_insert",
    "postgresql_update",
    "postgresql_delete",
    "postgresql_transaction",
    "postgresql_create_table",
    "postgresql_execute_function"
  ],
  "settings": {
    "default_timeout": 30,
    "connection_pool_size": 10,
    "pgvector_support": true
  }
}
```

## Credential Setup

When you configure PostgreSQL integration, you'll be prompted to provide:

1. **PostgreSQL Connection String** - Your PostgreSQL connection URI
   - Format: `postgresql://username:password@host:port/database`
   - Example: `postgresql://myuser:mypass@localhost:5432/mydb`
   - This field is encrypted and stored securely

**Admin Interface:**
- Navigate to **Workflows → Integrations → PostgreSQL**
- Enter your PostgreSQL connection string
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Tools

### Query Tool

Create `configs/tools/postgresql-query.json`:

```json
{
  "id": "postgresql_query",
  "name": "PostgreSQL Query",
  "description": "Executes a SQL query on PostgreSQL",
  "version": "1.0.0",
  "category": "database",
  "icon": "postgresql",
  "integration": "postgresql",
  "inputs": {
    "query": {
      "type": "string",
      "required": true,
      "label": "SQL Query",
      "description": "SQL SELECT query"
    },
    "parameters": {
      "type": "array",
      "required": false,
      "label": "Parameters",
      "description": "Query parameters for prepared statements"
    }
  },
  "outputs": {
    "rows": {
      "type": "array",
      "label": "Rows"
    },
    "row_count": {
      "type": "integer",
      "label": "Row Count"
    }
  },
  "handler": "Integrations\\PostgreSQL\\Tools\\Query"
}
```

### Insert Tool

Create `configs/tools/postgresql-insert.json`:

```json
{
  "id": "postgresql_insert",
  "name": "PostgreSQL Insert",
  "description": "Inserts a row into a PostgreSQL table",
  "version": "1.0.0",
  "category": "database",
  "icon": "postgresql",
  "integration": "postgresql",
  "inputs": {
    "table": {
      "type": "string",
      "required": true,
      "label": "Table Name"
    },
    "data": {
      "type": "object",
      "required": true,
      "label": "Data",
      "description": "Column-value pairs"
    },
    "returning": {
      "type": "string",
      "required": false,
      "label": "Returning",
      "description": "Columns to return (RETURNING clause)"
    }
  },
  "outputs": {
    "inserted_id": {
      "type": "integer",
      "label": "Inserted ID"
    },
    "row": {
      "type": "object",
      "label": "Inserted Row"
    }
  },
  "handler": "Integrations\\PostgreSQL\\Tools\\Insert"
}
```

### Transaction Tool

Create `configs/tools/postgresql-transaction.json`:

```json
{
  "id": "postgresql_transaction",
  "name": "PostgreSQL Transaction",
  "description": "Executes multiple queries in a transaction",
  "version": "1.0.0",
  "category": "database",
  "icon": "postgresql",
  "integration": "postgresql",
  "inputs": {
    "queries": {
      "type": "array",
      "required": true,
      "label": "Queries",
      "description": "Array of SQL queries to execute",
      "items": {
        "type": "object",
        "properties": {
          "query": "string",
          "parameters": "array"
        }
      }
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    },
    "results": {
      "type": "array",
      "label": "Results",
      "description": "Results from each query"
    }
  },
  "handler": "Integrations\\PostgreSQL\\Tools\\Transaction"
}
```

## pgvector Extension

PostgreSQL with pgvector extension supports vector operations for RAG:

```json
{
  "id": "postgresql_pgvector",
  "name": "PostgreSQL pgvector",
  "description": "PostgreSQL with pgvector extension for vector storage",
  "settings": {
    "pgvector_support": true,
    "vector_dimension": 1536
  },
  "tools": [
    "postgresql_create_vector_table",
    "postgresql_insert_vector",
    "postgresql_search_vectors"
  ]
}
```

## Example Workflows

**Store Workflow Data:**
```
Trigger: Workflow Executed
  ↓
Tool: Get Execution Data
  ↓
Tool: PostgreSQL Insert → "workflow_executions"
```

**Complex Query:**
```
Trigger: User Request → "Get Analytics"
  ↓
Tool: PostgreSQL Query → Complex aggregation
  ↓
Tool: Format Results
  ↓
Tool: Return Response
```

**Transaction Workflow:**
```
Trigger: Order Created
  ↓
Tool: PostgreSQL Transaction:
  ├─ Insert Order
  ├─ Update Inventory
  └─ Create Log Entry
```


