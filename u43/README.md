# U43 - WordPress Agentic Workflow Plugin

A powerful workflow automation plugin for WordPress with AI-powered decision making.

## Installation

1. Copy the `u43` folder to your WordPress `wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin panel
3. Go to **Workflows → Settings** to configure your OpenAI API key
4. Create your first workflow at **Workflows → Add New**

## Phase 1 Features

- ✅ Comment trigger (fires when a comment is posted)
- ✅ AI decision agent (uses OpenAI to analyze comments)
- ✅ WordPress actions (approve, spam, delete comments, send email)
- ✅ Simple workflow execution engine
- ✅ Basic admin interface

## Configuration

### OpenAI API Key

1. Go to **Workflows → Settings**
2. Enter your OpenAI API key
3. Save settings

Get your API key from: https://platform.openai.com/api-keys

## Usage

### Creating a Workflow

1. Go to **Workflows → Add New**
2. Enter a title and description
3. Select status (Draft or Published)
4. Click "Create Workflow"

The default workflow will:
- Trigger when a comment is posted
- Use AI to analyze the comment
- Automatically approve, spam, or delete based on AI decision

### Workflow Status

- **Draft**: Workflow is saved but not active
- **Published**: Workflow is active and will execute automatically

## Development

See the [Development Documentation](../docs/development/) for:
- Development plan
- Phase 1 implementation guide
- UI/UX design guide

## Requirements

- WordPress 5.8+
- PHP 7.4+
- OpenAI API key (for AI features)

## License

GPL v2 or later

