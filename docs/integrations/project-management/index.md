# Project Management Tools

Project and task management platform integrations.

## Available Integrations

- **[Jira](jira.md)** - Issue tracking and project management
- **[Trello](trello.md)** - Board and card management
- **[ClickUp](clickup.md)** - Task and workspace management
- **[Monday.com](monday.md)** - Board and item management
- **[Asana](asana.md)** - Task and project management

## Common Features

Project management integrations typically provide:
- Task/issue creation and management
- Status updates and transitions
- Comment and collaboration features
- Project and workspace organization
- Assignment and due date management

## Authentication Methods

Different tools use different authentication:
- **Jira**: Basic Auth (email + API token)
- **Trello**: OAuth1
- **ClickUp**: API Key
- **Monday.com**: API Key
- **Asana**: OAuth2

## Example Workflows

**Cross-Platform Sync:**
```
Trigger: Trello Card Created
  ↓
Tool: Get Trello Card Details
  ↓
Tool: Create ClickUp Task
  ↓
Tool: Add Comment to Trello Card
```

**Issue Automation:**
```
Trigger: Form Submitted → "Bug Report"
  ↓
Tool: Get Form Data
  ↓
Tool: Create Jira Issue
  ↓
Tool: Send Slack Notification
```


