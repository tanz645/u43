# Site Kit Google Analytics Integration

## Quick Overview

- OAuth2 authentication via Site Kit
- API-based tools for analytics data
- Scheduled triggers for threshold monitoring
- Real-time analytics data access

> **Note**: Site Kit is a WordPress plugin that manages its own OAuth authentication with Google. This integration uses Site Kit's existing authentication - no additional credentials needed in our plugin. The integration accesses Site Kit's stored tokens and API access.

## Example Workflow

```
Trigger: Threshold Reached → Sessions > 1000 (today)
  ↓
Tool: Get Realtime Data
  ↓
Tool: Send Email Alert to Admin
```

## Configuration Pattern

Site Kit integration uses OAuth2 authentication and API-based tools. See [Adding Custom Integrations](adding-integrations.md) for the complete pattern.

