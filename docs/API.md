# API Reference

## REST API Endpoints

### Flows

- `GET /wp-json/agentic-workflow/v1/flows` - List all flows
- `GET /wp-json/agentic-workflow/v1/flows/{id}` - Get flow details
- `POST /wp-json/agentic-workflow/v1/flows` - Create new flow
- `PUT /wp-json/agentic-workflow/v1/flows/{id}` - Update flow
- `DELETE /wp-json/agentic-workflow/v1/flows/{id}` - Delete flow
- `POST /wp-json/agentic-workflow/v1/flows/{id}/execute` - Execute flow

### Tools

- `GET /wp-json/agentic-workflow/v1/tools` - List all tools
- `GET /wp-json/agentic-workflow/v1/tools/{id}` - Get tool details
- `POST /wp-json/agentic-workflow/v1/tools/{id}/execute` - Execute tool

### Agents

- `GET /wp-json/agentic-workflow/v1/agents` - List all agents
- `GET /wp-json/agentic-workflow/v1/agents/{id}` - Get agent details
- `POST /wp-json/agentic-workflow/v1/agents/{id}/execute` - Execute agent

## PHP API

### Getting the Plugin Instance

```php
// Get plugin instance
$workflow = WP_Agentic_Workflow();
```

### Working with Registries

```php
// Get registries
$tools_registry = $workflow->get_tools_registry();
$agents_registry = $workflow->get_agents_registry();
$triggers_registry = $workflow->get_trigger_registry();
$actions_registry = $workflow->get_actions_registry();
```

### Executing Tools

```php
// Execute a tool
$result = $tools_registry->execute('wordpress_create_post', [
    'title' => 'Hello World',
    'content' => 'This is a test post'
]);
```

### Executing Agents

```php
// Execute an agent
$result = $agents_registry->execute('llm_decision_agent', [
    'prompt' => 'Should I publish this post?',
    'context' => ['post_id' => 123]
]);
```

### Managing Flows

```php
// Get flow manager
$flow_manager = $workflow->get_flow_manager();

// Execute a flow
$flow_manager->execute_flow('flow_id', [
    'trigger_data' => $data
]);

// Get flow details
$flow = $flow_manager->get_flow('flow_id');

// Create a new flow
$flow_id = $flow_manager->create_flow([
    'name' => 'My Workflow',
    'nodes' => [...],
    'edges' => [...]
]);
```

### Triggering Workflows

```php
// Trigger a workflow by event
$workflow->emit('custom_event', [
    'data' => $some_data
]);

// Trigger via trigger registry
$triggers_registry->trigger('wordpress_post_published', [
    'post_id' => 123,
    'post' => get_post(123)
]);
```

## API Authentication

All REST API endpoints require authentication. Use one of the following methods:

1. **WordPress Nonce**: Include `X-WP-Nonce` header
2. **Application Password**: Use WordPress Application Passwords
3. **OAuth**: Use OAuth2 authentication (if configured)

## Error Handling

All API endpoints return standard HTTP status codes:

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `500` - Internal Server Error

Error responses include a JSON body with error details:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400
  }
}
```

## Rate Limiting

API endpoints are rate-limited to prevent abuse:
- Default: 100 requests per minute per user
- Configurable via filters

```php
// Modify rate limit
add_filter('aw_api_rate_limit', function($limit) {
    return 200; // 200 requests per minute
});
```

