# WooCommerce Integration

> **Note**: Complete WooCommerce integration documentation is being prepared. For now, see the main [INTEGRATIONS.md](../INTEGRATIONS.md) file for examples.

## Quick Overview

- Integration config file defines WooCommerce connection
- Triggers for order events (created, status changed, etc.)
- Tools for order management (create, update, get orders)
- Handler classes implement the business logic

> **Note**: WooCommerce is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure WooCommerce is installed and activated.

## Example Workflow

```
Trigger: Order Status Changed → "processing"
  ↓
Tool: Get Order Details
  ↓
Agent: Check Inventory Availability
  ↓
Tool: Update Order Status → "completed"
```

## Configuration Pattern

WooCommerce integration follows the same configuration pattern as other integrations:
1. Create `configs/integrations/woocommerce.json`
2. Create trigger configs in `configs/triggers/`
3. Create tool configs in `configs/tools/`
4. Implement handler classes

See [Adding Custom Integrations](adding-integrations.md) for the complete pattern.

