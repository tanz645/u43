# HubSpot Integration

## Overview

HubSpot CRM integration enables workflows to create contacts, deals, and manage marketing automation.

## Integration Configuration

Create `configs/integrations/hubspot.json`:

```json
{
  "id": "hubspot",
  "name": "HubSpot",
  "description": "HubSpot CRM and marketing integration",
  "version": "1.0.0",
  "icon": "hubspot",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "triggers": [
    "hubspot_contact_created",
    "hubspot_deal_updated",
    "hubspot_form_submitted"
  ],
  "tools": [
    "hubspot_create_contact",
    "hubspot_update_contact",
    "hubspot_get_contact",
    "hubspot_create_deal",
    "hubspot_add_to_list",
    "hubspot_create_company"
  ],
  "api_base_url": "https://api.hubapi.com"
}
```

## Credential Setup

When you configure HubSpot integration, you'll be prompted to provide:

1. **API Key** - Your HubSpot API key (encrypted and stored securely)
   - Generate at: HubSpot Settings → Integrations → Private Apps → Create a private app

**Admin Interface:**
- Navigate to **Workflows → Integrations → HubSpot**
- Enter your HubSpot API key
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Tools

### Create Contact Tool

Create `configs/tools/hubspot-create-contact.json`:

```json
{
  "id": "hubspot_create_contact",
  "name": "Create HubSpot Contact",
  "description": "Creates a new contact in HubSpot",
  "version": "1.0.0",
  "category": "marketing",
  "icon": "hubspot",
  "integration": "hubspot",
  "inputs": {
    "email": {
      "type": "string",
      "required": true,
      "label": "Email"
    },
    "first_name": {
      "type": "string",
      "required": false,
      "label": "First Name"
    },
    "last_name": {
      "type": "string",
      "required": false,
      "label": "Last Name"
    },
    "phone": {
      "type": "string",
      "required": false,
      "label": "Phone"
    },
    "company": {
      "type": "string",
      "required": false,
      "label": "Company"
    },
    "properties": {
      "type": "object",
      "required": false,
      "label": "Custom Properties"
    }
  },
  "outputs": {
    "contact_id": {
      "type": "string",
      "label": "Contact ID"
    },
    "contact_url": {
      "type": "string",
      "label": "Contact URL"
    }
  },
  "handler": "Integrations\\HubSpot\\Tools\\CreateContact"
}
```

## Example Workflows

**Lead to CRM:**
```
Trigger: Form Submitted → "Contact Form"
  ↓
Tool: Get Form Data
  ↓
Tool: Create HubSpot Contact
  ↓
Tool: Add to Marketing List
```

