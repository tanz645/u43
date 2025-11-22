# Developer Hooks & Filters

## Integration Hooks

### Modify Integration Configuration

```php
add_filter('aw_integration_config', function($config, $integration_id) {
    // Modify integration config
    if ($integration_id === 'slack') {
        $config['settings']['custom_setting'] = 'value';
    }
    return $config;
}, 10, 2);
```

### Modify Tool Execution

```php
add_filter('aw_tool_execute', function($result, $tool_id, $inputs) {
    // Modify tool execution result
    if ($tool_id === 'slack_send_message') {
        $result['custom_field'] = 'custom_value';
    }
    return $result;
}, 10, 3);
```

### Modify Trigger Data

```php
add_filter('aw_trigger_data', function($data, $trigger_id) {
    // Modify trigger data before workflow execution
    if ($trigger_id === 'wpforms_form_submitted') {
        $data['custom_field'] = 'value';
    }
    return $data;
}, 10, 2);
```

### Before Tool Execution

```php
add_action('aw_before_tool_execute', function($tool_id, $inputs) {
    // Perform actions before tool execution
    // e.g., logging, validation, etc.
}, 10, 2);
```

### After Tool Execution

```php
add_action('aw_after_tool_execute', function($tool_id, $result) {
    // Perform actions after tool execution
    // e.g., logging, notifications, etc.
}, 10, 2);
```

## Custom Integration Registration

```php
add_action('aw_plugin_loaded', function() {
    // Register custom integration
    WP_Agentic_Workflow()->get_integration_registry()->register([
        'id' => 'custom_integration',
        'name' => 'Custom Integration',
        'config_path' => plugin_dir_path(__FILE__) . 'configs/integrations/custom.json'
    ]);
});
```

