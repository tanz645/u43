# MySQL/MariaDB Integration

## Overview

MySQL and MariaDB are popular open-source relational database systems, commonly used with WordPress.

## Integration Configuration

Create `configs/integrations/mysql.json`:

```json
{
  "id": "mysql",
  "name": "MySQL/MariaDB",
  "description": "MySQL and MariaDB database integration",
  "version": "1.0.0",
  "icon": "mysql",
  "authentication": {
    "type": "connection_string",
    "description": "MySQL connection string",
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
      "default": 3306,
      "label": "Port"
    },
    "database": {
      "type": "string",
      "required": true,
      "label": "Database Name"
    },
    "username": {
      "type": "string",
      "required": true,
      "label": "Username"
    },
    "password": {
      "type": "string",
      "required": true,
      "label": "Password",
      "storage": "encrypted"
    }
  },
  "tools": [
    "mysql_query",
    "mysql_insert",
    "mysql_update",
    "mysql_delete",
    "mysql_transaction",
    "mysql_create_table"
  ],
  "settings": {
    "default_timeout": 30,
    "connection_pool_size": 10,
    "charset": "utf8mb4"
  }
}
```

## Credential Setup

When you configure MySQL/MariaDB integration, you'll be prompted to provide:

1. **Host** - Database server hostname (default: localhost)
2. **Port** - Database server port (default: 3306)
3. **Database Name** - Name of the database to use
4. **Username** - Database username
5. **Password** - Database password (encrypted and stored securely)

**Admin Interface:**
- Navigate to **Workflows → Integrations → MySQL/MariaDB**
- Enter your database credentials
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Tools

Similar to PostgreSQL, MySQL integration provides:
- Query execution
- Insert/Update/Delete operations
- Transaction support
- Table creation

## WordPress Database Integration

Since WordPress uses MySQL/MariaDB, you can also integrate directly with WordPress database:

```json
{
  "id": "wordpress_db",
  "name": "WordPress Database",
  "description": "Direct access to WordPress database",
  "tools": [
    "wp_db_query",
    "wp_db_get_posts",
    "wp_db_get_users",
    "wp_db_custom_query"
  ],
  "settings": {
    "use_wpdb": true,
    "table_prefix": "wp_"
  }
}
```

## Example Workflows

**Custom Data Storage:**
```
Trigger: Form Submitted
  ↓
Tool: MySQL Insert → Custom table
  ↓
Tool: Update WordPress Post Meta
```

**Data Migration:**
```
Trigger: Scheduled → Daily
  ↓
Tool: MySQL Query → Get data
  ↓
Tool: Transform Data
  ↓
Tool: Insert into WordPress
```


