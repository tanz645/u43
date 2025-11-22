# Database Integrations

Database integrations enable workflows to interact with various database systems for data storage, retrieval, caching, and search operations.

## Available Database Integrations

### Relational Databases
- **[PostgreSQL](postgresql.md)** - Advanced open-source relational database
- **[MySQL/MariaDB](mysql.md)** - Popular relational database systems
- **[SQLite](sqlite.md)** - Lightweight file-based database

### NoSQL Databases
- **[MongoDB](mongodb.md)** - NoSQL document database

### Time-Series Databases
- **[ClickHouse](clickhouse.md)** - Column-oriented analytical database
- **[TimescaleDB](timescaledb.md)** - Time-series database built on PostgreSQL

### Search & Cache
- **[Redis](redis.md)** - In-memory data structure store and cache
- **[Elasticsearch](elasticsearch.md)** - Distributed search and analytics engine

## Common Use Cases

- **Data Storage**: Store workflow data, logs, and results
- **Caching**: Cache frequently accessed data for performance (Redis)
- **Search**: Full-text search and querying capabilities (Elasticsearch)
- **Session Management**: Store user sessions and state (Redis)
- **Queue Management**: Use databases as message queues (Redis)
- **Analytics**: Store and analyze workflow execution data
- **Time-Series Data**: Store metrics, logs, and time-stamped data (ClickHouse, TimescaleDB)
- **Real-Time Analytics**: Fast aggregations and analytical queries (ClickHouse)
- **Monitoring**: System and application monitoring metrics

## Common Patterns

All database integrations follow similar patterns:
- Connection configuration
- CRUD operations (Create, Read, Update, Delete)
- Query builders
- Transaction support
- Connection pooling

## Quick Start

1. Choose a database from the list above
2. Configure connection settings
3. Use database tools in workflows
4. Implement data operations

