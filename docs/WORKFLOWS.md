# Workflows and Nodes

## Overview

A **workflow** is a visual representation of an automated process, consisting of **nodes** connected by **edges**. Each node represents a step in the workflow, and edges define the flow of data between nodes.

## Workflow Creation Flow

### Step 1: Create a New Workflow

1. Navigate to **Workflows → Add New** in WordPress admin
2. Enter workflow details:
   - **Title**: Name of your workflow (e.g., "Auto-respond to Contact Form")
   - **Description**: Optional description of what the workflow does
   - **Status**: Choose "Draft" or "Published"
3. Click **Create Workflow**

### Step 2: Add Nodes

Once the workflow canvas is open, you can start adding nodes:

1. Click **+ Add Node** button or drag from the node palette
2. Select the type of node you want to add
3. Configure the node (see Node Types below)
4. Connect nodes by dragging from output ports to input ports
   - **Single connection**: Drag from one output to one input
   - **Multiple connections**: Drag from one output to multiple inputs (creates branches)
   - **Conditional connections**: Use condition nodes to create branches

### Step 3: Configure Nodes

Each node has:
- **Title**: Display name for the node (required)
- **Description**: Optional description of what this node does
- **Configuration**: Node-specific settings (varies by type)
- **Input/Output**: Data ports for connecting to other nodes

### Step 4: Save Workflow

- **Save as Draft**: Workflow is saved but not active
- **Publish**: Workflow becomes active and starts executing
- **Update**: Save changes to an existing workflow

## Node Types

### 1. Trigger Nodes

**Purpose**: Start a workflow when a specific event occurs.

**Types**:
- **WordPress Hook Trigger**: Fires on WordPress hooks (post published, user registered, etc.)
- **Form Submission Trigger**: Fires when a form is submitted (WPForms, Contact Form 7, etc.)
- **Scheduled Trigger**: Fires on a schedule (daily, weekly, cron expression)
- **Webhook Trigger**: Fires when a webhook URL is called
- **Manual Trigger**: Fires when manually triggered via API or admin

**Properties**:
- Title (required)
- Description (optional)
- Trigger configuration (hook name, schedule, webhook URL, etc.)
- Output data schema

**Example**:
```
Title: "New Post Published"
Description: "Triggers when a new post is published"
Configuration:
  - Hook: wp_insert_post
  - Post Status: publish
  - Post Type: post
Outputs:
  - post_id
  - post_title
  - post_content
  - post_author
```

### 2. Action/Tool Nodes

**Purpose**: Perform a specific action or operation.

**Types**:
- **WordPress Actions**: Create post, update user, send email, etc.
- **Integration Actions**: Send Slack message, create Jira issue, etc.
- **Database Actions**: Query database, insert data, etc.
- **HTTP Actions**: Make API calls, webhooks, etc.
- **File Actions**: Read/write files, upload to storage, etc.

**Properties**:
- Title (required)
- Description (optional)
- Tool selection (which tool to execute)
- Input parameters (mapped from previous nodes or static values)
- Output data

**Example**:
```
Title: "Send Email Notification"
Description: "Sends an email to the admin when a post is published"
Tool: wp_mail_smtp_send_email
Inputs:
  - to: admin@example.com
  - subject: "New Post: {{post_title}}"
  - message: "A new post '{{post_title}}' was published."
Outputs:
  - success
  - message_id
```

### 3. Agent Nodes

**Purpose**: Use AI agents for decision-making, text processing, or complex operations.

**Types**:
- **LLM Agent**: Uses Large Language Models (ChatGPT, Claude, Gemini, etc.)
- **Decision Agent**: Makes decisions based on conditions
- **Text Analysis Agent**: Analyzes and processes text
- **RAG Agent**: Retrieval-Augmented Generation with vector stores

**Properties**:
- Title (required)
- Description (optional)
- Agent type selection
- Model/provider selection
- Prompt/instructions
- Context data
- Output format

**Example**:
```
Title: "Analyze Post Content"
Description: "Uses AI to analyze post content and extract key topics"
Agent: LLM Agent
Model: gpt-4
Prompt: "Analyze the following post and extract 3 main topics: {{post_content}}"
Outputs:
  - topics (array)
  - summary
  - sentiment
```

### 4. Condition Nodes

**Purpose**: Branch workflow execution based on conditions.

**Types**:
- **If/Else Node**: Simple conditional branching
- **Switch Node**: Multiple condition branches
- **Filter Node**: Filter array data based on conditions

**Properties**:
- Title (required)
- Description (optional)
- Condition type (equals, contains, greater than, etc.)
- Comparison value
- True/false branches

**Example**:
```
Title: "Check Post Category"
Description: "Routes workflow based on post category"
Condition:
  - Field: post_category
  - Operator: equals
  - Value: "News"
True Branch: → Send to News Team
False Branch: → Send to General Team
```

### 5. Delay/Wait Nodes

**Purpose**: Pause workflow execution for a specified time.

**Types**:
- **Delay Node**: Wait for a fixed duration
- **Wait Until Node**: Wait until a specific time/date
- **Wait for Event Node**: Wait for a specific event

**Properties**:
- Title (required)
- Description (optional)
- Delay duration (seconds, minutes, hours, days)
- Or wait until date/time

**Example**:
```
Title: "Wait 5 Minutes"
Description: "Waits 5 minutes before sending follow-up email"
Delay: 5 minutes
```

### 6. Loop Nodes

**Purpose**: Iterate over data (arrays, collections).

**Types**:
- **For Each Node**: Loop over array items
- **While Loop Node**: Loop while condition is true
- **Repeat Node**: Repeat action N times

**Properties**:
- Title (required)
- Description (optional)
- Loop type
- Data source
- Loop body nodes

**Example**:
```
Title: "Process Each Form Field"
Description: "Processes each field in the form submission"
Loop Type: For Each
Data Source: form_fields
Loop Body:
  - Validate Field
  - Store Field Data
```

### 7. Data Transformation Nodes

**Purpose**: Transform, map, or manipulate data.

**Types**:
- **Set Node**: Set/update data values
- **Code Node**: Execute custom code (JavaScript/PHP)
- **JSON Node**: Parse/stringify JSON
- **Merge Node**: Merge multiple data sources
- **Split Node**: Split data into multiple outputs

**Properties**:
- Title (required)
- Description (optional)
- Transformation type
- Mapping rules
- Output schema

**Example**:
```
Title: "Format User Data"
Description: "Formats user data for API call"
Transformation:
  - Map: first_name → firstName
  - Map: last_name → lastName
  - Add: fullName = firstName + " " + lastName
```

### 8. Error Handling Nodes

**Purpose**: Handle errors and exceptions.

**Types**:
- **Try/Catch Node**: Handle errors gracefully
- **Retry Node**: Retry failed operations
- **Error Handler Node**: Custom error handling logic

**Properties**:
- Title (required)
- Description (optional)
- Error handling strategy
- Retry count/interval
- Fallback actions

**Example**:
```
Title: "Handle API Error"
Description: "Retries API call up to 3 times on failure"
Retry Count: 3
Retry Interval: 5 seconds
On Failure: Send Error Notification
```

## Node Connections

### Multiple Output Connections

**Yes, nodes can connect to multiple nodes simultaneously!**

Each node can have **multiple output connections**, allowing you to:
- **Branch workflows** - Send data to different paths
- **Parallel execution** - Execute multiple nodes at the same time
- **Broadcast data** - Send the same data to multiple nodes
- **Complex routing** - Route data based on different conditions

### Connection Types

**Single Connection**:
```
Node A → Node B
```
One node connects to one other node (sequential execution).

**Multiple Connections (Branching)**:
```
        → Node B
Node A → Node C
        → Node D
```
One node connects to multiple nodes (parallel execution).

**Conditional Branching**:
```
        → Node B (if condition is true)
Node A → Node C (if condition is false)
        → Node D (always executes)
```
Condition node branches to different paths.

### Connection Rules

1. **Output Ports**: Each node can have multiple output ports
2. **Input Ports**: Each node can have multiple input ports
3. **Multiple Outputs**: One output port can connect to multiple input ports
4. **Data Broadcasting**: Same data is sent to all connected nodes
5. **Parallel Execution**: All connected nodes execute simultaneously (unless configured otherwise)

### Examples

**Example 1: Broadcast to Multiple Actions**
```
Trigger: Form Submitted
    ↓
    ├─→ Send Email to User
    ├─→ Create CRM Lead
    └─→ Log to Database
```
All three actions execute in parallel with the same form data.

**Example 2: Conditional Branching**
```
Trigger: Order Created
    ↓
Condition: Order Total > $100
    ├─→ (True) Send VIP Email
    └─→ (False) Send Standard Email
```
Only one branch executes based on condition.

**Example 3: Multiple Output Ports**
```
Action: Get User Data
    ├─→ Output 1 (user_info) → Update Profile
    ├─→ Output 2 (user_email) → Send Welcome Email
    └─→ Output 3 (user_id) → Create Account
```
Different outputs connect to different nodes.

**Example 4: Complex Workflow**
```
Trigger: Post Published
    ↓
Agent: Analyze Content
    ├─→ Condition: Is News?
    │   ├─→ (True) → Send to News Team
    │   └─→ (False) → Send to General Team
    └─→ Always Execute:
        ├─→ Update SEO Meta
        ├─→ Share on Social Media
        └─→ Send Notification
```
Multiple branches with conditional and parallel execution.

### Connection Configuration

When connecting nodes, you can:
- **Connect one output to multiple inputs** - Drag from one output port to multiple input ports
- **Specify data mapping** - Map which output fields go to which input fields
- **Add conditions** - Add conditions to connections (for conditional branching)
- **Set execution order** - Configure parallel vs sequential execution

### Visual Representation

In the workflow builder:
- **Output ports** appear on the right side of a node
- **Input ports** appear on the left side of a node
- **Connections** are drawn as lines/edges between ports
- **Multiple connections** from one port appear as multiple lines

## Node Structure

### Node Schema

```json
{
  "id": "node_123",
  "type": "action",
  "title": "Send Email",
  "description": "Sends notification email",
  "position": {
    "x": 100,
    "y": 200
  },
  "config": {
    "tool_id": "wp_mail_smtp_send_email",
    "inputs": {
      "to": "admin@example.com",
      "subject": "{{post_title}}",
      "message": "{{post_content}}"
    }
  },
  "inputs": [
    {
      "id": "input_1",
      "name": "data",
      "type": "object"
    }
  ],
  "outputs": [
    {
      "id": "output_1",
      "name": "result",
      "type": "object"
    }
  ],
  "connections": {
    "input_1": ["node_122:output_1"],
    "output_1": ["node_124:input_1", "node_125:input_1", "node_126:input_1"]  // Multiple connections!
  }
}
```

### Node Properties

**Common Properties** (all node types):
- `id`: Unique node identifier
- `type`: Node type (trigger, action, agent, condition, etc.)
- `title`: Display name (required)
- `description`: Optional description
- `position`: X/Y coordinates on canvas
- `enabled`: Whether node is active (default: true)
- `notes`: User notes/annotations

**Type-Specific Properties**:
- Trigger nodes: Trigger configuration
- Action nodes: Tool selection and parameters
- Agent nodes: Agent type, model, prompt
- Condition nodes: Condition rules
- Delay nodes: Duration/time settings

## Workflow States

### Draft

- Workflow is saved but **not active**
- Can be edited and tested
- Will not execute automatically
- Can be manually tested via "Test Workflow" button

### Published

- Workflow is **active** and will execute
- Triggers are listening for events
- Can still be edited (changes take effect immediately)
- Can be paused/unpublished

### Paused

- Workflow is temporarily disabled
- Configuration is preserved
- Can be resumed later

### Archived

- Workflow is disabled and hidden
- Can be restored later
- Used for old/unused workflows

## Workflow Execution

### Execution Flow

1. **Trigger Fires**: Event occurs (hook, schedule, webhook, etc.)
2. **Context Created**: Execution context initialized with trigger data
3. **Node Execution**: 
   - **Sequential**: Nodes execute in order (one after another)
   - **Parallel**: Multiple connected nodes execute simultaneously
   - **Conditional**: Only matching branches execute
4. **Data Flow**: 
   - Output from one node becomes input to connected nodes
   - Same data can be sent to multiple nodes (broadcasting)
   - Each connected node receives a copy of the output data
5. **Error Handling**: Errors are caught and handled by error nodes
6. **Completion**: Workflow completes when all active branches finish

### Execution Modes

**Synchronous**: 
- Executes immediately
- Waits for completion
- Returns result

**Asynchronous**:
- Executes in background
- Returns immediately
- Results logged for later review

## Workflow Monitoring

### Execution Logs

View execution history for each workflow:

**Log Information**:
- Execution ID
- Start/end time
- Duration
- Status (success, failed, running)
- Node execution details
- Data at each step
- Errors/warnings

**Access**:
- Navigate to **Workflows → [Workflow Name] → Logs**
- Filter by date, status, or search
- View detailed execution trace

### Performance Metrics

**Metrics Tracked**:
- **Total Executions**: Number of times workflow ran
- **Success Rate**: Percentage of successful executions
- **Average Duration**: Average execution time
- **Last Execution**: When workflow last ran
- **Error Count**: Number of failed executions
- **Node Performance**: Execution time per node

**Dashboard**:
- Overview of all workflows
- Quick stats and health indicators
- Recent executions
- Error alerts

### Error Tracking

**Error Types**:
- **Node Errors**: Errors within a specific node
- **Connection Errors**: Issues with data flow between nodes
- **Timeout Errors**: Workflow execution timeout
- **Validation Errors**: Invalid configuration or data

**Error Details**:
- Error message
- Stack trace
- Node where error occurred
- Input data at time of error
- Timestamp

**Error Handling**:
- Automatic retry (if configured)
- Error notification (email, Slack, etc.)
- Error logging for debugging
- Workflow pause on repeated errors

### Analytics Dashboard

**Workflow Analytics**:
- Execution frequency over time
- Success/failure trends
- Performance trends
- Most used nodes
- Peak execution times

**Node Analytics**:
- Execution count per node
- Average execution time
- Error rate per node
- Data volume processed

## Workflow Management

### Workflow List

View all workflows with:
- Title and description
- Status (Draft/Published/Paused)
- Last execution time
- Success rate
- Quick actions (Edit, Duplicate, Delete, Test)

### Workflow Actions

**Edit**: Open workflow in builder
**Duplicate**: Create a copy of workflow
**Delete**: Remove workflow (with confirmation)
**Test**: Manually trigger workflow execution
**Export**: Export workflow as JSON
**Import**: Import workflow from JSON
**Publish/Unpublish**: Toggle active status
**Archive**: Move to archived workflows

### Version History

- Track changes to workflows
- View previous versions
- Restore to previous version
- Compare versions
- See who made changes and when

## Example Workflows

### Example 1: Simple Sequential Workflow

**Title**: "Auto-respond to Contact Form"

**Description**: "Automatically responds to contact form submissions and creates a lead in CRM"

**Nodes**:

1. **Trigger Node**: "Form Submitted"
   - Type: Form Submission Trigger
   - Form: Contact Form 7
   - Outputs: form_data, user_email, user_name

2. **Agent Node**: "Generate Response"
   - Type: LLM Agent
   - Prompt: "Generate a friendly thank you email for: {{form_data}}"
   - Outputs: email_content

3. **Action Node**: "Send Thank You Email"
   - Type: Send Email
   - To: {{user_email}}
   - Subject: "Thank you for contacting us"
   - Message: {{email_content}}

4. **Condition Node**: "Check if Lead"
   - Condition: form_data.interest == "sales"
   - True: → Create CRM Lead
   - False: → End

5. **Action Node**: "Create CRM Lead"
   - Type: HubSpot Create Contact
   - Email: {{user_email}}
   - Name: {{user_name}}
   - Source: "Contact Form"

**Status**: Published
**Last Execution**: 2 hours ago
**Success Rate**: 98.5%
**Average Duration**: 2.3 seconds

### Example 2: Multiple Connections (Parallel Execution)

**Title**: "Process New Order"

**Description**: "When an order is created, perform multiple actions in parallel"

**Workflow Structure**:
```
Trigger: Order Created
    ↓
    ├─→ Action: Send Confirmation Email (parallel)
    ├─→ Action: Update Inventory (parallel)
    ├─→ Action: Create Shipping Label (parallel)
    └─→ Action: Log to Analytics (parallel)
```

**Key Points**:
- All 4 actions execute **simultaneously** (parallel)
- Each action receives the same order data
- Workflow completes when all actions finish
- Faster execution than sequential

### Example 3: Complex Branching

**Title**: "Smart Content Publishing"

**Description**: "Publishes content and routes to different teams based on category"

**Workflow Structure**:
```
Trigger: Post Published
    ↓
Agent: Analyze Content
    ↓
Condition: Category Check
    ├─→ (News) → Send to News Team
    ├─→ (Tech) → Send to Tech Team
    └─→ (General) → Send to General Team
    ↓
Always Execute (parallel):
    ├─→ Update SEO Meta
    ├─→ Share on Social Media
    └─→ Send Notification
```

**Key Points**:
- Conditional branching based on category
- Multiple parallel actions after condition
- Different data paths for different categories

## Best Practices

1. **Naming**: Use descriptive titles for nodes and workflows
2. **Documentation**: Add descriptions to explain complex logic
3. **Testing**: Test workflows in draft mode before publishing
4. **Error Handling**: Always include error handling nodes
5. **Monitoring**: Regularly check execution logs and metrics
6. **Optimization**: Monitor performance and optimize slow nodes
7. **Version Control**: Use version history for important workflows
8. **Backup**: Export workflows before major changes

## Summary

✅ **Nodes** are the building blocks of workflows  
✅ **Multiple node types** for different purposes (triggers, actions, agents, conditions, etc.)  
✅ **Title and description** for each node  
✅ **Multiple connections** - One node can connect to multiple nodes simultaneously  
✅ **Parallel execution** - Multiple nodes can execute at the same time  
✅ **Conditional branching** - Route data to different paths based on conditions  
✅ **Draft/Published** workflow states  
✅ **Comprehensive monitoring** with logs, metrics, and error tracking  
✅ **Workflow management** with version history and analytics  

The visual workflow builder makes it easy to create complex automations by connecting nodes together! You can connect one node to multiple nodes for parallel execution, branching, and complex workflow patterns.

