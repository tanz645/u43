# ClickHouse Integration

## Overview

ClickHouse is a column-oriented database management system designed for real-time analytical data processing. Perfect for analytics, metrics, and time-series data.

## Integration Configuration

Create `configs/integrations/clickhouse.json`:

```json
{
  "id": "clickhouse",
  "name": "ClickHouse",
  "description": "ClickHouse analytical database integration",
  "version": "1.0.0",
  "icon": "clickhouse",
  "authentication": {
    "type": "basic_auth",
    "description": "Username and password",
    "storage": "encrypted"
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
      "default": 8123,
      "label": "Port",
      "description": "HTTP port (8123) or native port (9000)"
    },
    "database": {
      "type": "string",
      "required": true,
      "label": "Database Name"
    },
    "use_native_protocol": {
      "type": "boolean",
      "required": false,
      "default": false,
      "label": "Use Native Protocol",
      "description": "Use native TCP protocol instead of HTTP"
    }
  },
  "tools": [
    "clickhouse_query",
    "clickhouse_insert",
    "clickhouse_bulk_insert",
    "clickhouse_create_table",
    "clickhouse_create_materialized_view",
    "clickhouse_optimize_table"
  ],
  "settings": {
    "default_timeout": 300,
    "max_execution_time": 300,
    "insert_block_size": 1048576
  }
}
```

## Credential Setup

When you configure ClickHouse integration, you'll be prompted to provide:

1. **Host** - ClickHouse server hostname (default: localhost)
2. **Port** - ClickHouse server port (default: 8123 for HTTP, 9000 for native)
3. **Database Name** - Name of the database to use
4. **Username** - ClickHouse username
5. **Password** - ClickHouse password (encrypted and stored securely)
6. **Use Native Protocol** - Whether to use native TCP protocol instead of HTTP

**Admin Interface:**
- Navigate to **Workflows → Integrations → ClickHouse**
- Enter your ClickHouse connection details
- Enter your database credentials
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Tools

### Query Tool

Create `configs/tools/clickhouse-query.json`:

```json
{
  "id": "clickhouse_query",
  "name": "ClickHouse Query",
  "description": "Executes a SQL query on ClickHouse",
  "version": "1.0.0",
  "category": "database",
  "icon": "clickhouse",
  "integration": "clickhouse",
  "inputs": {
    "query": {
      "type": "string",
      "required": true,
      "label": "SQL Query",
      "description": "ClickHouse SQL query"
    },
    "format": {
      "type": "enum",
      "required": false,
      "default": "JSON",
      "options": ["JSON", "JSONEachRow", "CSV", "TSV"],
      "label": "Response Format"
    },
    "parameters": {
      "type": "object",
      "required": false,
      "label": "Parameters",
      "description": "Query parameters"
    }
  },
  "outputs": {
    "data": {
      "type": "array",
      "label": "Data"
    },
    "rows": {
      "type": "integer",
      "label": "Rows Returned"
    },
    "statistics": {
      "type": "object",
      "label": "Query Statistics"
    }
  },
  "handler": "Integrations\\ClickHouse\\Tools\\Query"
}
```

### Insert Tool

Create `configs/tools/clickhouse-insert.json`:

```json
{
  "id": "clickhouse_insert",
  "name": "ClickHouse Insert",
  "description": "Inserts data into ClickHouse table",
  "version": "1.0.0",
  "category": "database",
  "icon": "clickhouse",
  "integration": "clickhouse",
  "inputs": {
    "table": {
      "type": "string",
      "required": true,
      "label": "Table Name"
    },
    "data": {
      "type": "array",
      "required": true,
      "label": "Data",
      "description": "Array of objects to insert"
    },
    "format": {
      "type": "enum",
      "required": false,
      "default": "JSONEachRow",
      "options": ["JSONEachRow", "CSV", "TSV"],
      "label": "Data Format"
    }
  },
  "outputs": {
    "inserted_rows": {
      "type": "integer",
      "label": "Inserted Rows"
    }
  },
  "handler": "Integrations\\ClickHouse\\Tools\\Insert"
}
```

### Create Table Tool

Create `configs/tools/clickhouse-create-table.json`:

```json
{
  "id": "clickhouse_create_table",
  "name": "Create ClickHouse Table",
  "description": "Creates a new table in ClickHouse",
  "version": "1.0.0",
  "category": "database",
  "icon": "clickhouse",
  "integration": "clickhouse",
  "inputs": {
    "table_name": {
      "type": "string",
      "required": true,
      "label": "Table Name"
    },
    "engine": {
      "type": "enum",
      "required": true,
      "label": "Engine",
      "options": [
        "MergeTree",
        "ReplacingMergeTree",
        "SummingMergeTree",
        "AggregatingMergeTree",
        "CollapsingMergeTree",
        "VersionedCollapsingMergeTree"
      ],
      "default": "MergeTree"
    },
    "columns": {
      "type": "array",
      "required": true,
      "label": "Columns",
      "description": "Array of column definitions",
      "items": {
        "type": "object",
        "properties": {
          "name": "string",
          "type": "string",
          "default": "string"
        }
      }
    },
    "order_by": {
      "type": "array",
      "required": true,
      "label": "Order By",
      "description": "Columns for sorting key",
      "items": {
        "type": "string"
      }
    },
    "partition_by": {
      "type": "string",
      "required": false,
      "label": "Partition By",
      "description": "Partition expression"
    },
    "ttl": {
      "type": "string",
      "required": false,
      "label": "TTL",
      "description": "Time to live expression"
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    }
  },
  "handler": "Integrations\\ClickHouse\\Tools\\CreateTable"
}
```

## Example Workflows

**Store Workflow Metrics:**
```
Trigger: Workflow Executed
  ↓
Tool: Collect Metrics:
  ├─ Execution Time
  ├─ Success/Failure
  └─ Resource Usage
  ↓
Tool: ClickHouse Insert → "workflow_metrics"
```

**Analytics Dashboard:**
```
Trigger: Scheduled → Hourly
  ↓
Tool: ClickHouse Query → Aggregate metrics
  ↓
Tool: Generate Dashboard Data
  ↓
Tool: Update Dashboard Widget
```

**Time-Series Analysis:**
```
Trigger: User Request → "Get Trends"
  ↓
Tool: ClickHouse Query → Time-series aggregation
  ↓
Tool: Format Chart Data
  ↓
Tool: Return Visualization
```

## Use Cases

- **Workflow Analytics**: Store execution metrics, performance data
- **Time-Series Data**: Logs, metrics, events over time
- **Real-Time Analytics**: Fast aggregations and queries
- **Data Warehousing**: Large-scale data storage and analysis
- **Monitoring**: System and application monitoring metrics


