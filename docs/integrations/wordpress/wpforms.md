# WPForms Integration

WPForms integrates easily using the same configuration pattern as other WordPress plugins.

## Quick Overview

- ✅ Same configuration pattern as Contact Form 7 and WooCommerce
- ✅ Standard WordPress hooks (`wpforms_process_complete`, `wpforms_entry_created`)
- ✅ Built-in entry storage (no additional plugins needed)
- ✅ Rich field data access
- ✅ Payment integration support

> **Note**: WPForms is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure WPForms is installed and activated.

## Integration Configuration

Create `configs/integrations/wpforms.json`:

```json
{
  "id": "wpforms",
  "name": "WPForms",
  "description": "WPForms form integration",
  "version": "1.0.0",
  "icon": "wpforms",
  "plugin_dependency": {
    "plugin": "wpforms-lite/wpforms.php",
    "min_version": "1.8.0"
  },
  "triggers": [
    "wpforms_form_submitted",
    "wpforms_entry_created",
    "wpforms_payment_complete"
  ],
  "tools": [
    "wpforms_get_form",
    "wpforms_get_entries",
    "wpforms_get_entry",
    "wpforms_create_entry",
    "wpforms_send_notification"
  ],
  "settings": {
    "entry_storage": true,
    "payment_support": true
  }
}
```

## Trigger Configuration

Create `configs/triggers/wpforms-form-submitted.json`:

```json
{
  "id": "wpforms_form_submitted",
  "name": "Form Submitted",
  "description": "Triggers when a WPForms form is submitted",
  "version": "1.0.0",
  "category": "forms",
  "icon": "form",
  "integration": "wpforms",
  "hook": "wpforms_process_complete",
  "outputs": {
    "form_id": {
      "type": "integer",
      "label": "Form ID"
    },
    "form_title": {
      "type": "string",
      "label": "Form Title"
    },
    "entry_id": {
      "type": "integer",
      "label": "Entry ID"
    },
    "fields": {
      "type": "object",
      "label": "Form Fields"
    },
    "user_id": {
      "type": "integer",
      "label": "User ID"
    }
  },
  "handler": "Integrations\\WPForms\\Triggers\\FormSubmitted"
}
```

## Tool Configuration

Create `configs/tools/wpforms-get-entry.json`:

```json
{
  "id": "wpforms_get_entry",
  "name": "Get WPForms Entry",
  "description": "Retrieves a WPForms entry by ID",
  "version": "1.0.0",
  "category": "forms",
  "icon": "form",
  "integration": "wpforms",
  "inputs": {
    "entry_id": {
      "type": "integer",
      "required": true,
      "label": "Entry ID"
    }
  },
  "outputs": {
    "entry_id": {
      "type": "integer",
      "label": "Entry ID"
    },
    "form_id": {
      "type": "integer",
      "label": "Form ID"
    },
    "fields": {
      "type": "object",
      "label": "Form Fields"
    }
  },
  "handler": "Integrations\\WPForms\\Tools\\GetEntry"
}
```

## Handler Classes

See the main [INTEGRATIONS.md](../INTEGRATIONS.md) file for complete PHP handler class examples.

## Example Workflows

**Lead Qualification:**
```
Trigger: Form Submitted → "Contact Form"
  ↓
Tool: Get Entry Details
  ↓
Agent: Analyze Lead Quality (LLM Agent)
  ↓
Condition: Lead Score > 80
  ↓
Tool: Create CRM Contact
```

**Payment Processing:**
```
Trigger: Payment Complete → "Checkout Form"
  ↓
Tool: Get Entry Details
  ↓
Tool: Create WooCommerce Order
  ↓
Tool: Send Confirmation Email
```

