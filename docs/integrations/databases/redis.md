# Redis Integration

## Overview

Redis is an in-memory data structure store used as a database, cache, and message broker. Perfect for caching, session storage, and real-time data.

## Integration Configuration

Create `configs/integrations/redis.json`:

```json
{
  "id": "redis",
  "name": "Redis",
  "description": "Redis in-memory data store integration",
  "version": "1.0.0",
  "icon": "redis",
  "authentication": {
    "type": "password",
    "description": "Redis password (if required)",
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
      "default": 6379,
      "label": "Port"
    },
    "database": {
      "type": "integer",
      "required": false,
      "default": 0,
      "label": "Database",
      "description": "Redis database number (0-15)"
    }
  },
  "tools": [
    "redis_set",
    "redis_get",
    "redis_delete",
    "redis_exists",
    "redis_incr",
    "redis_expire",
    "redis_list_push",
    "redis_list_pop",
    "redis_hash_set",
    "redis_hash_get",
    "redis_publish",
    "redis_subscribe"
  ],
  "settings": {
    "default_ttl": 3600,
    "connection_timeout": 5
  }
}
```

## Credential Setup

When you configure Redis integration, you'll be prompted to provide:

1. **Host** - Redis server hostname (default: localhost)
2. **Port** - Redis server port (default: 6379)
3. **Database** - Redis database number (0-15, default: 0)
4. **Password** - Redis password (if required, encrypted and stored securely)

**Admin Interface:**
- Navigate to **Workflows → Integrations → Redis**
- Enter your Redis connection details
- If your Redis instance requires authentication, enter the password
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Tools

### Set Key-Value Tool

Create `configs/tools/redis-set.json`:

```json
{
  "id": "redis_set",
  "name": "Redis Set",
  "description": "Sets a key-value pair in Redis",
  "version": "1.0.0",
  "category": "database",
  "icon": "redis",
  "integration": "redis",
  "inputs": {
    "key": {
      "type": "string",
      "required": true,
      "label": "Key"
    },
    "value": {
      "type": "string",
      "required": true,
      "label": "Value"
    },
    "ttl": {
      "type": "integer",
      "required": false,
      "label": "TTL (seconds)",
      "description": "Time to live in seconds"
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    }
  },
  "handler": "Integrations\\Redis\\Tools\\Set"
}
```

### Get Value Tool

Create `configs/tools/redis-get.json`:

```json
{
  "id": "redis_get",
  "name": "Redis Get",
  "description": "Gets a value from Redis by key",
  "version": "1.0.0",
  "category": "database",
  "icon": "redis",
  "integration": "redis",
  "inputs": {
    "key": {
      "type": "string",
      "required": true,
      "label": "Key"
    }
  },
  "outputs": {
    "value": {
      "type": "string",
      "label": "Value"
    },
    "exists": {
      "type": "boolean",
      "label": "Exists"
    }
  },
  "handler": "Integrations\\Redis\\Tools\\Get"
}
```

### List Operations Tool

Create `configs/tools/redis-list-push.json`:

```json
{
  "id": "redis_list_push",
  "name": "Redis List Push",
  "description": "Pushes values to a Redis list",
  "version": "1.0.0",
  "category": "database",
  "icon": "redis",
  "integration": "redis",
  "inputs": {
    "key": {
      "type": "string",
      "required": true,
      "label": "List Key"
    },
    "values": {
      "type": "array",
      "required": true,
      "label": "Values",
      "items": {
        "type": "string"
      }
    },
    "side": {
      "type": "enum",
      "required": false,
      "default": "right",
      "options": ["left", "right"],
      "label": "Side",
      "description": "Push to left (LPUSH) or right (RPUSH)"
    }
  },
  "outputs": {
    "length": {
      "type": "integer",
      "label": "List Length"
    }
  },
  "handler": "Integrations\\Redis\\Tools\\ListPush"
}
```

### Publish/Subscribe Tool

Create `configs/tools/redis-publish.json`:

```json
{
  "id": "redis_publish",
  "name": "Redis Publish",
  "description": "Publishes a message to a Redis channel",
  "version": "1.0.0",
  "category": "database",
  "icon": "redis",
  "integration": "redis",
  "inputs": {
    "channel": {
      "type": "string",
      "required": true,
      "label": "Channel"
    },
    "message": {
      "type": "string",
      "required": true,
      "label": "Message"
    }
  },
  "outputs": {
    "subscribers": {
      "type": "integer",
      "label": "Subscribers Count"
    }
  },
  "handler": "Integrations\\Redis\\Tools\\Publish"
}
```

## Example Workflows

**Cache Workflow Results:**
```
Trigger: Workflow Executed
  ↓
Tool: Get Execution Result
  ↓
Tool: Redis Set → Cache for 1 hour
```

**Session Management:**
```
Trigger: User Login
  ↓
Tool: Generate Session Token
  ↓
Tool: Redis Set → Store session
  ↓
Tool: Set Expiry → 24 hours
```

**Message Queue:**
```
Trigger: Form Submitted
  ↓
Tool: Redis List Push → "form_queue"
  ↓
Trigger: Background Worker
  ↓
Tool: Redis List Pop → Process queue
```

**Pub/Sub Notifications:**
```
Trigger: Order Created
  ↓
Tool: Redis Publish → "orders" channel
  ↓
Multiple Subscribers:
  ├─ Email Service
  ├─ Slack Notification
  └─ Analytics Service
```


