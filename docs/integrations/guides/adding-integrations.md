# Adding Custom Integrations

## Quick Start Guide

1. **Get API Credentials**: Obtain API key/token from the service
2. **Create Integration Config**: Add JSON config file in `configs/integrations/`
3. **Create Tool Configs**: Define tools you need in `configs/tools/`
4. **Create Trigger Configs**: Define triggers in `configs/triggers/`
5. **Implement Handlers**: Create PHP handler classes extending base classes
6. **Test**: Use the tools in workflows

## Step-by-Step Example

### 1. Create Integration Configuration

Create `configs/integrations/my-service.json`:

```json
{
  "id": "my_service",
  "name": "My Service",
  "description": "Integration with My Service",
  "version": "1.0.0",
  "icon": "my-service",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "tools": [
    "my_service_action"
  ],
  "api_base_url": "https://api.myservice.com/v1"
}
```

### 2. Create Tool Configuration

Create `configs/tools/my-service-action.json`:

```json
{
  "id": "my_service_action",
  "name": "My Service Action",
  "description": "Performs an action in My Service",
  "version": "1.0.0",
  "category": "custom",
  "icon": "my-service",
  "integration": "my_service",
  "inputs": {
    "param1": {
      "type": "string",
      "required": true,
      "label": "Parameter 1"
    }
  },
  "outputs": {
    "result": {
      "type": "string",
      "label": "Result"
    }
  },
  "handler": "Integrations\\MyService\\Tools\\MyAction"
}
```

### 3. Implement Handler Class

Create `includes/integrations/my-service/tools/class-my-action.php`:

```php
<?php
namespace Integrations\MyService\Tools;

use WP_Agentic_Workflow\Tools\Tool_Base;

class My_Action extends Tool_Base {
    
    public function execute($inputs) {
        // Get API key
        $api_key = $this->get_api_key();
        
        // Make API call
        $response = wp_remote_post('https://api.myservice.com/v1/action', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'param1' => $inputs['param1']
            ])
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('API error: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return [
            'result' => $body['result']
        ];
    }
    
    protected function get_api_key() {
        return get_option('aw_my_service_api_key', '');
    }
}
```

### 4. Auto-Discovery

The plugin automatically discovers configurations in:
- `wp-content/plugins/{plugin-name}/aw-configs/` - Plugin-specific configs
- `wp-content/uploads/aw-configs/` - User-uploaded configs
- `wp-content/themes/{theme-name}/aw-configs/` - Theme-specific configs

## Common Integration Patterns

All integrations follow the same pattern:
1. **Authentication**: OAuth2, API Key, or Basic Auth
2. **Configuration**: JSON config files
3. **Tools**: Action-based tools (create, update, get)
4. **Triggers**: Webhook-based or polling-based triggers
5. **Handler Classes**: PHP classes implementing base interfaces

