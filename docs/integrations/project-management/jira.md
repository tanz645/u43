# Jira Integration

## Overview

Jira integration enables creating issues, updating statuses, adding comments, and managing sprints.

## Authentication

Jira uses Basic Auth with email and API token.

## Credential Setup

When you configure Jira integration, you'll be prompted to provide:

1. **Jira URL** - Your Jira instance URL
   - Format: `https://yourcompany.atlassian.net`
2. **Email** - Your Jira account email
3. **API Token** - Your Jira API token (encrypted and stored securely)
   - Generate at: https://id.atlassian.com/manage-profile/security/api-tokens

**Admin Interface:**
- Navigate to **Workflows → Integrations → Jira**
- Enter your Jira instance URL
- Enter your email and API token
- Click **Test Connection** to verify
- Click **Save Credentials** to store securely

See [Credential Management](../../CREDENTIALS.md) for detailed information on how credentials are collected, encrypted, and stored.

## Integration Configuration

Create `configs/integrations/jira.json`:

```json
{
  "id": "jira",
  "name": "Jira",
  "description": "Atlassian Jira integration",
  "version": "1.0.0",
  "icon": "jira",
  "authentication": {
    "type": "basic_auth",
    "description": "Use Jira email and API token",
    "storage": "encrypted"
  },
  "triggers": [
    "jira_issue_created",
    "jira_issue_updated",
    "jira_issue_commented",
    "jira_sprint_started"
  ],
  "tools": [
    "jira_create_issue",
    "jira_update_issue",
    "jira_get_issue",
    "jira_add_comment",
    "jira_transition_issue",
    "jira_create_sprint"
  ],
  "inputs": {
    "jira_url": {
      "type": "string",
      "required": true,
      "label": "Jira URL",
      "description": "Your Jira instance URL (e.g., https://yourcompany.atlassian.net)"
    }
  },
  "api_base_url": "{jira_url}/rest/api/3"
}
```

## Tools

### Create Issue Tool

Create `configs/tools/jira-create-issue.json`:

```json
{
  "id": "jira_create_issue",
  "name": "Create Jira Issue",
  "description": "Creates a new Jira issue",
  "version": "1.0.0",
  "category": "project-management",
  "icon": "jira",
  "integration": "jira",
  "inputs": {
    "project_key": {
      "type": "string",
      "required": true,
      "label": "Project Key"
    },
    "issue_type": {
      "type": "enum",
      "required": true,
      "label": "Issue Type",
      "options": ["Task", "Bug", "Story", "Epic"],
      "default": "Task"
    },
    "summary": {
      "type": "string",
      "required": true,
      "label": "Summary"
    },
    "description": {
      "type": "string",
      "required": false,
      "label": "Description"
    },
    "assignee": {
      "type": "string",
      "required": false,
      "label": "Assignee"
    },
    "priority": {
      "type": "enum",
      "required": false,
      "options": ["Lowest", "Low", "Medium", "High", "Highest"],
      "label": "Priority"
    }
  },
  "outputs": {
    "issue_key": {
      "type": "string",
      "label": "Issue Key"
    },
    "issue_url": {
      "type": "string",
      "label": "Issue URL"
    }
  },
  "handler": "Integrations\\Jira\\Tools\\CreateIssue"
}
```

## Example Workflows

**Bug Report Automation:**
```
Trigger: Form Submitted → "Bug Report"
  ↓
Tool: Get Form Data
  ↓
Agent: Analyze Bug Report (LLM Agent)
  ↓
Tool: Create Jira Issue
  ↓
Tool: Send Slack Notification
```

