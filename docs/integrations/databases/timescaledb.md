# TimescaleDB Integration

## Overview

TimescaleDB is an open-source time-series database built on PostgreSQL. It combines the power of PostgreSQL with time-series optimizations.

## Integration Configuration

Create `configs/integrations/timescaledb.json`:

```json
{
  "id": "timescaledb",
  "name": "TimescaleDB",
  "description": "TimescaleDB time-series database integration",
  "version": "1.0.0",
  "icon": "timescaledb",
  "authentication": {
    "type": "connection_string",
    "description": "PostgreSQL/TimescaleDB connection string",
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
    "timescaledb_create_hypertable",
    "timescaledb_insert",
    "timescaledb_query",
    "timescaledb_continuous_aggregate",
    "timescaledb_add_retention_policy",
    "timescaledb_compress_chunks",
    "timescaledb_query_time_bucket"
  ],
  "settings": {
    "default_timeout": 30,
    "connection_pool_size": 10,
    "chunk_time_interval": "1 day"
  }
}
```

## Credential Setup

When you configure TimescaleDB integration, you'll be prompted to provide:

1. **PostgreSQL Connection String** - Your TimescaleDB connection URI
   - Format: `postgresql://username:password@host:port/database`
   - Example: `postgresql://myuser:mypass@localhost:5432/mydb`
   - This field is encrypted and stored securely

**Admin Interface:**
- Navigate to **Workflows → Integrations → TimescaleDB**
- Enter your TimescaleDB connection string
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Tools

### Create Hypertable Tool

Create `configs/tools/timescaledb-create-hypertable.json`:

```json
{
  "id": "timescaledb_create_hypertable",
  "name": "Create TimescaleDB Hypertable",
  "description": "Creates a hypertable from a regular PostgreSQL table",
  "version": "1.0.0",
  "category": "database",
  "icon": "timescaledb",
  "integration": "timescaledb",
  "inputs": {
    "table_name": {
      "type": "string",
      "required": true,
      "label": "Table Name"
    },
    "time_column": {
      "type": "string",
      "required": true,
      "label": "Time Column",
      "description": "Name of the time column"
    },
    "partitioning_column": {
      "type": "string",
      "required": false,
      "label": "Partitioning Column",
      "description": "Optional space partitioning column"
    },
    "number_partitions": {
      "type": "integer",
      "required": false,
      "label": "Number of Partitions",
      "description": "For space partitioning"
    },
    "chunk_time_interval": {
      "type": "string",
      "required": false,
      "default": "1 day",
      "label": "Chunk Time Interval",
      "description": "Interval for time partitioning (e.g., '1 day', '1 hour')"
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    },
    "hypertable_name": {
      "type": "string",
      "label": "Hypertable Name"
    }
  },
  "handler": "Integrations\\TimescaleDB\\Tools\\CreateHypertable"
}
```

### Insert Tool

Create `configs/tools/timescaledb-insert.json`:

```json
{
  "id": "timescaledb_insert",
  "name": "TimescaleDB Insert",
  "description": "Inserts data into a TimescaleDB hypertable",
  "version": "1.0.0",
  "category": "database",
  "icon": "timescaledb",
  "integration": "timescaledb",
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
      "description": "Row data with time column"
    }
  },
  "outputs": {
    "inserted": {
      "type": "boolean",
      "label": "Inserted"
    }
  },
  "handler": "Integrations\\TimescaleDB\\Tools\\Insert"
}
```

### Query with Time Bucket Tool

Create `configs/tools/timescaledb-query-time-bucket.json`:

```json
{
  "id": "timescaledb_query_time_bucket",
  "name": "TimescaleDB Time Bucket Query",
  "description": "Queries data with time_bucket aggregation",
  "version": "1.0.0",
  "category": "database",
  "icon": "timescaledb",
  "integration": "timescaledb",
  "inputs": {
    "table": {
      "type": "string",
      "required": true,
      "label": "Table Name"
    },
    "time_column": {
      "type": "string",
      "required": true,
      "label": "Time Column"
    },
    "bucket_interval": {
      "type": "string",
      "required": true,
      "label": "Bucket Interval",
      "description": "Time bucket interval (e.g., '1 hour', '1 day')"
    },
    "aggregations": {
      "type": "array",
      "required": false,
      "label": "Aggregations",
      "description": "Array of aggregation functions",
      "items": {
        "type": "object",
        "properties": {
          "column": "string",
          "function": "string",
          "alias": "string"
        }
      }
    },
    "filter": {
      "type": "object",
      "required": false,
      "label": "Filter",
      "description": "WHERE clause conditions"
    },
    "order_by": {
      "type": "string",
      "required": false,
      "label": "Order By"
    }
  },
  "outputs": {
    "results": {
      "type": "array",
      "label": "Results"
    },
    "bucket_count": {
      "type": "integer",
      "label": "Bucket Count"
    }
  },
  "handler": "Integrations\\TimescaleDB\\Tools\\QueryTimeBucket"
}
```

### Continuous Aggregate Tool

Create `configs/tools/timescaledb-continuous-aggregate.json`:

```json
{
  "id": "timescaledb_continuous_aggregate",
  "name": "Create Continuous Aggregate",
  "description": "Creates a continuous aggregate view for pre-computed aggregations",
  "version": "1.0.0",
  "category": "database",
  "icon": "timescaledb",
  "integration": "timescaledb",
  "inputs": {
    "view_name": {
      "type": "string",
      "required": true,
      "label": "View Name"
    },
    "source_table": {
      "type": "string",
      "required": true,
      "label": "Source Table"
    },
    "query": {
      "type": "string",
      "required": true,
      "label": "Aggregation Query",
      "description": "SQL query with time_bucket and aggregations"
    },
    "refresh_interval": {
      "type": "string",
      "required": false,
      "label": "Refresh Interval",
      "description": "How often to refresh (e.g., '1 hour')"
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    },
    "view_name": {
      "type": "string",
      "label": "View Name"
    }
  },
  "handler": "Integrations\\TimescaleDB\\Tools\\CreateContinuousAggregate"
}
```

### Retention Policy Tool

Create `configs/tools/timescaledb-add-retention-policy.json`:

```json
{
  "id": "timescaledb_add_retention_policy",
  "name": "Add Retention Policy",
  "description": "Adds a data retention policy to automatically drop old data",
  "version": "1.0.0",
  "category": "database",
  "icon": "timescaledb",
  "integration": "timescaledb",
  "inputs": {
    "hypertable": {
      "type": "string",
      "required": true,
      "label": "Hypertable Name"
    },
    "retention_period": {
      "type": "string",
      "required": true,
      "label": "Retention Period",
      "description": "How long to keep data (e.g., '30 days', '1 year')"
    },
    "if_not_exists": {
      "type": "boolean",
      "required": false,
      "default": true,
      "label": "If Not Exists"
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    },
    "policy_id": {
      "type": "integer",
      "label": "Policy ID"
    }
  },
  "handler": "Integrations\\TimescaleDB\\Tools\\AddRetentionPolicy"
}
```

## Example Workflows

**Store Time-Series Metrics:**
```
Trigger: Workflow Executed
  ↓
Tool: Collect Metrics:
  ├─ Timestamp
  ├─ Execution Time
  ├─ Success Status
  └─ Resource Usage
  ↓
Tool: TimescaleDB Insert → "workflow_metrics" hypertable
```

**Time-Series Analytics:**
```
Trigger: User Request → "Get Metrics Trend"
  ↓
Tool: TimescaleDB Time Bucket Query:
  ├─ Bucket: 1 hour
  ├─ Aggregate: AVG(execution_time)
  └─ Filter: Last 7 days
  ↓
Tool: Format Chart Data
  ↓
Tool: Return Visualization
```

**Automated Data Retention:**
```
Trigger: Scheduled → Daily
  ↓
Tool: TimescaleDB Retention Policy:
  ├─ Drop data older than 90 days
  └─ Compress chunks older than 7 days
```

**Continuous Aggregation:**
```
Trigger: Table Created → "workflow_metrics"
  ↓
Tool: Create Continuous Aggregate:
  ├─ Hourly averages
  ├─ Daily summaries
  └─ Auto-refresh every hour
```

## Use Cases

- **Workflow Metrics**: Store execution times, success rates, resource usage over time
- **Performance Monitoring**: Track system performance metrics
- **Analytics Dashboards**: Pre-computed aggregations for fast dashboard queries
- **IoT Data**: Store sensor data, device metrics
- **Application Metrics**: Application performance monitoring (APM) data
- **Log Aggregation**: Time-stamped log entries with fast queries

## Key Features

- **Hypertables**: Automatically partitioned tables for time-series data
- **Continuous Aggregates**: Materialized views that auto-refresh
- **Data Retention**: Automatic data lifecycle management
- **Compression**: Efficient storage with automatic compression
- **Time Buckets**: Built-in time_bucket function for aggregations
- **PostgreSQL Compatibility**: Full PostgreSQL SQL support

## Best Practices

1. **Chunk Interval**: Choose appropriate chunk_time_interval based on data volume
2. **Indexing**: Create indexes on frequently queried columns
3. **Compression**: Enable compression for older chunks
4. **Retention**: Set retention policies to manage storage
5. **Continuous Aggregates**: Use for frequently accessed aggregations
6. **Batch Inserts**: Batch multiple rows for better performance


