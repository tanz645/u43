# Advanced Coupons Integration

## Overview

Advanced Coupons is a WooCommerce extension for advanced coupon functionality.

> **Note**: Advanced Coupons is a WooCommerce extension (WordPress plugin) - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure WooCommerce and Advanced Coupons are installed and activated.

## Integration Configuration

Create `configs/integrations/advanced-coupons.json`:

```json
{
  "id": "advanced_coupons",
  "name": "Advanced Coupons",
  "description": "Advanced Coupons WooCommerce extension",
  "version": "1.0.0",
  "icon": "coupon",
  "plugin_dependency": {
    "plugin": "advanced-coupons-for-woocommerce-free/advanced-coupons-for-woocommerce-free.php",
    "min_version": "1.0.0"
  },
  "triggers": [
    "advanced_coupon_applied",
    "advanced_coupon_created"
  ],
  "tools": [
    "advanced_coupon_create",
    "advanced_coupon_apply",
    "advanced_coupon_get_stats"
  ],
  "integration": "woocommerce"
}
```

## Example Workflows

**Dynamic Coupon Generation:**
```
Trigger: Customer Reaches Threshold
  ↓
Tool: Create Advanced Coupon
  ↓
Tool: Apply to Customer
  ↓
Tool: Send Email Notification
```

