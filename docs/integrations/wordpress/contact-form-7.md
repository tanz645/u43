# Contact Form 7 Integration

> **Note**: Complete Contact Form 7 integration documentation is being prepared. See [WPForms Integration](wpforms.md) for a similar form integration example.

## Quick Overview

- Form submission triggers
- Tools to get form data and send emails
- Integration with Flamingo plugin for submission storage

> **Note**: Contact Form 7 is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure Contact Form 7 is installed and activated.

## Example Workflow

```
Trigger: Form Submitted → "Contact Form"
  ↓
Tool: Get Form Submission Data
  ↓
Tool: Send Email (Thank You Message)
  ↓
Tool: Create WordPress Post (Lead Entry)
```

## Configuration Pattern

Contact Form 7 integration follows the same configuration pattern as WPForms. See [WPForms Integration](wpforms.md) for detailed examples.

