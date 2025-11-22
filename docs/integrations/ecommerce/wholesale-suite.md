# Wholesale Suite Integration

## Overview

Wholesale Suite is a WooCommerce extension for B2B wholesale functionality.

> **Note**: Wholesale Suite is a WooCommerce extension (WordPress plugin) - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure WooCommerce and Wholesale Suite are installed and activated.

## Integration Configuration

Create `configs/integrations/wholesale-suite.json`:

```json
{
  "id": "wholesale_suite",
  "name": "Wholesale Suite",
  "description": "Wholesale Suite WooCommerce extension",
  "version": "1.0.0",
  "icon": "wholesale",
  "plugin_dependency": {
    "plugin": "woocommerce-wholesale-prices/woocommerce-wholesale-prices.php",
    "min_version": "1.0.0"
  },
  "triggers": [
    "wholesale_order_created",
    "wholesale_price_updated"
  ],
  "tools": [
    "wholesale_get_pricing",
    "wholesale_update_pricing",
    "wholesale_get_wholesale_roles"
  ],
  "integration": "woocommerce"
}
```

## Example Workflows

**Wholesale Order Processing:**
```
Trigger: Wholesale Order Created
  ↓
Tool: Get Order Details
  ↓
Tool: Apply Wholesale Pricing
  ↓
Tool: Send Wholesale Confirmation
```

