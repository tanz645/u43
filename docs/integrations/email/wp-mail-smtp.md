# WP Mail SMTP Integration

## Overview

WP Mail SMTP integration enables reliable email sending through various SMTP providers.

> **Note**: WP Mail SMTP is a WordPress plugin that manages its own SMTP credentials. This integration uses WP Mail SMTP's existing configuration - no additional credentials needed in our plugin. The integration simply uses the `wp_mail()` function which WP Mail SMTP intercepts.

## Integration Configuration

Create `configs/integrations/wp-mail-smtp.json`:

```json
{
  "id": "wp_mail_smtp",
  "name": "WP Mail SMTP",
  "description": "WP Mail SMTP email integration",
  "version": "1.0.0",
  "icon": "email",
  "plugin_dependency": {
    "plugin": "wp-mail-smtp/wp_mail_smtp.php",
    "min_version": "3.0.0"
  },
  "tools": [
    "wp_mail_smtp_send_email",
    "wp_mail_smtp_get_logs",
    "wp_mail_smtp_test_connection"
  ],
  "settings": {
    "use_wp_mail": true,
    "log_emails": true
  }
}
```

## Tools

### Send Email Tool

Create `configs/tools/wp-mail-smtp-send-email.json`:

```json
{
  "id": "wp_mail_smtp_send_email",
  "name": "Send Email via WP Mail SMTP",
  "description": "Sends an email using WP Mail SMTP configuration",
  "version": "1.0.0",
  "category": "email",
  "icon": "email",
  "integration": "wp_mail_smtp",
  "inputs": {
    "to": {
      "type": "string",
      "required": true,
      "label": "To"
    },
    "subject": {
      "type": "string",
      "required": true,
      "label": "Subject"
    },
    "message": {
      "type": "string",
      "required": true,
      "label": "Message"
    },
    "headers": {
      "type": "array",
      "required": false,
      "label": "Headers"
    },
    "attachments": {
      "type": "array",
      "required": false,
      "label": "Attachments"
    }
  },
  "outputs": {
    "success": {
      "type": "boolean",
      "label": "Success"
    },
    "message_id": {
      "type": "string",
      "label": "Message ID"
    }
  },
  "handler": "Integrations\\WPMailSMTP\\Tools\\SendEmail"
}
```

## Example Workflows

**Email Notification:**
```
Trigger: Form Submitted
  ↓
Tool: Get Form Data
  ↓
Tool: Send Email via WP Mail SMTP
```

