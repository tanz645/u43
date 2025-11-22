# WordPress Core Integration

## Overview

The plugin integrates seamlessly with WordPress core functionality through hooks, events, and filters.

> **Note**: WordPress core integration - **no credentials needed**! This integration uses WordPress hooks, filters, and database access directly. No additional setup required.

## Hook-Based Triggers

Workflows can be triggered by WordPress hooks:

```php
// Example: Trigger workflow on post publish
add_action('wp_insert_post', function($post_id) {
    WP_Agentic_Workflow()->get_trigger_registry()
        ->trigger('wordpress_post_published', [
            'post_id' => $post_id,
            'post' => get_post($post_id)
        ]);
}, 10, 1);
```

## Event System

Custom event system for workflow triggers:

```php
// Emit custom event
WP_Agentic_Workflow()->emit('custom_event', [
    'data' => $some_data
]);

// Workflow automatically triggered if configured
```

## Filter Integration

Workflows can modify WordPress data through filters:

```php
// Example: Modify post content via workflow
add_filter('the_content', function($content) {
    return WP_Agentic_Workflow()
        ->apply_workflow_filter('modify_content', $content);
});
```

## Integration Methods

There are three primary ways for third-party plugins to integrate:

1. **Configuration-Based Integration** (Recommended): Add configuration files that are automatically discovered
2. **Programmatic Integration**: Use PHP hooks and filters to register components
3. **Hybrid Approach**: Combine configuration files with custom PHP handlers

