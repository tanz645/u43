# PushEngage Integration

## Overview

PushEngage integration enables sending push notifications to subscribers.

## Integration Configuration

Create `configs/integrations/pushengage.json`:

```json
{
  "id": "pushengage",
  "name": "PushEngage",
  "description": "PushEngage push notification integration",
  "version": "1.0.0",
  "icon": "notification",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "tools": [
    "pushengage_send_notification",
    "pushengage_send_segment_notification",
    "pushengage_get_subscribers"
  ],
  "api_base_url": "https://api.pushengage.com/v1"
}
```

## Credential Setup

When you configure PushEngage integration, you'll be prompted to provide:

1. **API Key** - Your PushEngage API key (encrypted and stored securely)
   - Get from: PushEngage Dashboard → Settings → API

**Admin Interface:**
- Navigate to **Workflows → Integrations → PushEngage**
- Enter your PushEngage API key
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Example Workflows

**Push Notification:**
```
Trigger: Post Published
  ↓
Tool: PushEngage Send Notification
```

