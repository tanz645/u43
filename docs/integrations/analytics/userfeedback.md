# UserFeedback Integration

## Overview

UserFeedback integration enables collecting and processing user feedback through workflows.

> **Note**: UserFeedback is a WordPress plugin - **no credentials needed**! This integration uses WordPress hooks and database access directly. Just ensure UserFeedback is installed and activated.

## Integration Configuration

Create `configs/integrations/userfeedback.json`:

```json
{
  "id": "userfeedback",
  "name": "UserFeedback",
  "description": "UserFeedback survey integration",
  "version": "1.0.0",
  "icon": "feedback",
  "plugin_dependency": {
    "plugin": "userfeedback-lite/userfeedback.php",
    "min_version": "1.0.0"
  },
  "triggers": [
    "userfeedback_survey_submitted",
    "userfeedback_response_received"
  ],
  "tools": [
    "userfeedback_get_responses",
    "userfeedback_create_survey",
    "userfeedback_get_survey_stats"
  ]
}
```

## Example Workflows

**Feedback Processing:**
```
Trigger: Survey Submitted
  ↓
Tool: Get Survey Response
  ↓
Agent: Analyze Sentiment (LLM Agent)
  ↓
Tool: Route to Appropriate Team
```

