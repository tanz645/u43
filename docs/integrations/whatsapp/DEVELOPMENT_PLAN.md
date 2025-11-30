# WhatsApp Integration - Development Plan

## Overview

This document outlines the development plan for integrating WhatsApp into the WordPress Agentic Workflow plugin. The integration will support multiple connection methods (phone number, QR code, webhook), comprehensive triggers for WhatsApp events, and extensive actions for messaging and group management.

## Prerequisites

- WordPress Agentic Workflow plugin architecture in place
- Credential Manager with encryption support (already exists)
- Integration configuration system (already exists)
- Tool and trigger registry system (already exists)
- REST API infrastructure (already exists)
- Modular admin handler architecture (already exists)

## Goals

- Add comprehensive WhatsApp integration with multiple connection methods
- Support phone number and QR code authentication
- Webhook support for real-time message events
- Comprehensive triggers and actions for WhatsApp messaging
- Group WhatsApp nodes with WhatsApp icon in node palette

## Tasks

### 2.9.1 Integration Configuration
- [ ] Create integration config: `configs/integrations/whatsapp.json`
- [ ] Define authentication methods (phone number, QR code, API token)
- [ ] Define webhook configuration structure
- [ ] Define business ID and phone number fields
- [ ] Add WhatsApp icon mapping

### 2.9.2 Credential Management & Settings UI
- [x] Create WhatsApp settings page: `admin/views/whatsapp-settings.php` (integrated into main settings)
- [x] Add phone number input field with validation
- [x] Add QR code scanner component (using webcam or image upload)
- [x] Add webhook URL input field (with HTTPS support)
- [x] Add Business ID input field
- [x] Add API token/access token input (if using WhatsApp Business API)
- [x] Add connection status indicator
- [x] Add "Test Connection" button
- [x] Store credentials securely (encrypted)
- [x] Add settings section in main Settings page
- [x] Add webhook verify token field
- [x] Add HTTPS support for webhook URLs (Meta requirement)

### 2.9.3 WhatsApp Connection Handler
- [ ] Create connection handler: `includes/integrations/whatsapp/class-whatsapp-connection.php`
- [ ] Implement phone number authentication method
- [ ] Implement QR code generation/scanning method
- [ ] Implement webhook registration
- [ ] Implement connection validation
- [ ] Handle connection errors gracefully
- [ ] Support multiple WhatsApp account instances

### 2.9.4 Webhook Handler
- [x] Create webhook endpoint: REST API route `/u43/v1/webhooks/whatsapp`
- [x] Implement webhook verification (GET request with challenge/response)
- [x] Implement webhook signature verification (if applicable)
- [x] Parse incoming webhook payloads
- [x] Route webhook events to appropriate triggers
- [x] Handle webhook errors and retries
- [x] Log webhook events for debugging
- [x] Support public endpoint access (bypass WordPress authentication)
- [x] Handle WordPress query parameter conversion (dots to underscores)
- [x] Return plain text challenge for Meta verification

### 2.9.5 WhatsApp Triggers
- [ ] **New Message Trigger**: `configs/triggers/whatsapp-message-received.json`
  - Handler: `includes/triggers/whatsapp/class-message-received-trigger.php`
  - Outputs: message_id, from, to, message_text, message_type, timestamp, media_url (if media)
  
- [ ] **Message Sent Trigger**: `configs/triggers/whatsapp-message-sent.json`
  - Handler: `includes/triggers/whatsapp/class-message-sent-trigger.php`
  - Outputs: message_id, to, message_text, status, timestamp

- [ ] **Message Delivered Trigger**: `configs/triggers/whatsapp-message-delivered.json`
  - Handler: `includes/triggers/whatsapp/class-message-delivered-trigger.php`
  - Outputs: message_id, to, delivered_at, read_at

- [ ] **Message Read Trigger**: `configs/triggers/whatsapp-message-read.json`
  - Handler: `includes/triggers/whatsapp/class-message-read-trigger.php`
  - Outputs: message_id, from, read_at

- [ ] **Message Failed Trigger**: `configs/triggers/whatsapp-message-failed.json`
  - Handler: `includes/triggers/whatsapp/class-message-failed-trigger.php`
  - Outputs: message_id, to, error_code, error_message, failed_at

- [ ] **Contact Joined Trigger**: `configs/triggers/whatsapp-contact-joined.json`
  - Handler: `includes/triggers/whatsapp/class-contact-joined-trigger.php`
  - Outputs: contact_number, contact_name, joined_at

- [ ] **Contact Left Trigger**: `configs/triggers/whatsapp-contact-left.json`
  - Handler: `includes/triggers/whatsapp/class-contact-left-trigger.php`
  - Outputs: contact_number, contact_name, left_at

- [ ] **Group Created Trigger**: `configs/triggers/whatsapp-group-created.json`
  - Handler: `includes/triggers/whatsapp/class-group-created-trigger.php`
  - Outputs: group_id, group_name, created_by, created_at

- [ ] **Group Updated Trigger**: `configs/triggers/whatsapp-group-updated.json`
  - Handler: `includes/triggers/whatsapp/class-group-updated-trigger.php`
  - Outputs: group_id, group_name, update_type, updated_by, updated_at

### 2.9.6 WhatsApp Actions/Tools
- [ ] **Send Message Tool**: `configs/tools/whatsapp-send-message.json`
  - Handler: `includes/tools/whatsapp/class-send-message.php`
  - Inputs: to (phone number), message (text), media_url (optional), media_type (optional)
  - Outputs: message_id, status, sent_at

- [ ] **Send Media Tool**: `configs/tools/whatsapp-send-media.json`
  - Handler: `includes/tools/whatsapp/class-send-media.php`
  - Inputs: to, media_url, media_type (image/video/document/audio), caption (optional)
  - Outputs: message_id, status, sent_at

- [ ] **Send Template Message Tool**: `configs/tools/whatsapp-send-template.json`
  - Handler: `includes/tools/whatsapp/class-send-template.php`
  - Inputs: to, template_name, template_params (array), language_code
  - Outputs: message_id, status, sent_at

- [ ] **Reply to Message Tool**: `configs/tools/whatsapp-reply-message.json`
  - Handler: `includes/tools/whatsapp/class-reply-message.php`
  - Inputs: message_id (original), reply_text, media_url (optional)
  - Outputs: message_id, status, sent_at

- [ ] **Mark as Read Tool**: `configs/tools/whatsapp-mark-read.json`
  - Handler: `includes/tools/whatsapp/class-mark-read.php`
  - Inputs: message_id
  - Outputs: success, read_at

- [ ] **Get Contact Info Tool**: `configs/tools/whatsapp-get-contact.json`
  - Handler: `includes/tools/whatsapp/class-get-contact.php`
  - Inputs: phone_number
  - Outputs: contact_name, contact_number, profile_picture_url, is_business, is_verified

- [ ] **Create Group Tool**: `configs/tools/whatsapp-create-group.json`
  - Handler: `includes/tools/whatsapp/class-create-group.php`
  - Inputs: group_name, participants (array of phone numbers), description (optional)
  - Outputs: group_id, group_name, created_at

- [ ] **Add to Group Tool**: `configs/tools/whatsapp-add-to-group.json`
  - Handler: `includes/tools/whatsapp/class-add-to-group.php`
  - Inputs: group_id, phone_numbers (array)
  - Outputs: success, added_count

- [ ] **Remove from Group Tool**: `configs/tools/whatsapp-remove-from-group.json`
  - Handler: `includes/tools/whatsapp/class-remove-from-group.php`
  - Inputs: group_id, phone_numbers (array)
  - Outputs: success, removed_count

- [ ] **Get Group Info Tool**: `configs/tools/whatsapp-get-group-info.json`
  - Handler: `includes/tools/whatsapp/class-get-group-info.php`
  - Inputs: group_id
  - Outputs: group_id, group_name, participants (array), created_at, description

- [ ] **Send Location Tool**: `configs/tools/whatsapp-send-location.json`
  - Handler: `includes/tools/whatsapp/class-send-location.php`
  - Inputs: to, latitude, longitude, name (optional), address (optional)
  - Outputs: message_id, status, sent_at

- [ ] **Send Contact Tool**: `configs/tools/whatsapp-send-contact.json`
  - Handler: `includes/tools/whatsapp/class-send-contact.php`
  - Inputs: to, contact_name, contact_number, contact_vcard (optional)
  - Outputs: message_id, status, sent_at

- [ ] **Delete Message Tool**: `configs/tools/whatsapp-delete-message.json`
  - Handler: `includes/tools/whatsapp/class-delete-message.php`
  - Inputs: message_id, for_everyone (boolean)
  - Outputs: success, deleted_at

### 2.9.7 WhatsApp API Client
- [ ] Create API client base: `includes/integrations/whatsapp/class-whatsapp-api-client.php`
- [ ] Implement HTTP request methods (GET, POST, PUT, DELETE)
- [ ] Handle authentication (token-based)
- [ ] Implement rate limiting
- [ ] Handle API errors and retries
- [ ] Support both WhatsApp Business API and WhatsApp Cloud API
- [ ] Implement webhook verification

### 2.9.8 Error Handling
- [ ] Create error handler: `includes/integrations/whatsapp/class-whatsapp-error-handler.php`
- [ ] Map WhatsApp API error codes to user-friendly messages
- [ ] Handle connection errors
- [ ] Handle authentication errors
- [ ] Handle rate limit errors
- [ ] Handle webhook errors
- [ ] Log errors for debugging

### 2.9.9 UI Integration - Node Palette
- [ ] Update `NodePalette.jsx` to group WhatsApp nodes
- [ ] Add WhatsApp icon (📱) to icon mapping
- [ ] Group all WhatsApp triggers under "WhatsApp" category
- [ ] Group all WhatsApp actions under "WhatsApp" category
- [ ] Add WhatsApp icon to all WhatsApp-related nodes
- [ ] Update node search to filter WhatsApp nodes

### 2.9.10 Node Configuration UI
- [ ] Update `NodeConfigPanel.jsx` to handle WhatsApp node configs
- [ ] Add phone number input with validation
- [ ] Add media upload for media actions
- [ ] Add template selector for template messages
- [ ] Add contact selector/input
- [ ] Show credential requirements (token, phone number, etc.)
- [ ] Display connection status in node config

### 2.9.11 Documentation
- [ ] Create WhatsApp integration guide: `docs/integrations/whatsapp/whatsapp.md`
- [ ] Document authentication methods
- [ ] Document all triggers and their outputs
- [ ] Document all actions and their inputs/outputs
- [ ] Document webhook setup
- [ ] Document error handling
- [ ] Add example workflows
- [ ] Update main integrations index

### 2.9.12 Testing
- [ ] Unit tests for WhatsApp API client
- [ ] Unit tests for triggers
- [ ] Unit tests for actions
- [ ] Integration tests for webhook handling
- [ ] Integration tests for message sending/receiving
- [ ] Test QR code scanning
- [ ] Test phone number authentication
- [ ] Test error scenarios

## Deliverables
- ✅ WhatsApp integration configuration file
- ✅ WhatsApp settings page with phone number, QR code, webhook, and Business ID inputs
- ✅ WhatsApp connection handler supporting multiple auth methods
- ✅ Webhook endpoint for receiving WhatsApp events
- ✅ Comprehensive set of WhatsApp triggers (message received, sent, delivered, read, failed, contact events, group events)
- ✅ Comprehensive set of WhatsApp actions (send message, media, template, reply, mark read, contact management, group management, location, contact card, delete)
- ✅ WhatsApp API client with error handling
- ✅ WhatsApp nodes grouped with WhatsApp icon in node palette
- ✅ Node configuration UI for WhatsApp nodes
- ✅ Complete documentation
- ✅ Test coverage

## Admin Handler Architecture

The WhatsApp integration uses the modular admin handler architecture:

- **Handler Location**: `/admin/handlers/class-whatsapp-handler.php`
- **Namespace**: `U43\Admin\Handlers\WhatsApp_Handler`
- **Auto-Loading**: Automatically discovered and loaded by `Admin::load_handlers()`

### Handler Responsibilities

The WhatsApp handler manages all admin-side AJAX requests:

- `ajax_test_whatsapp_connection()` - Tests WhatsApp API connection
- `ajax_generate_whatsapp_qr()` - Generates QR code for authentication

### Adding New Admin Functionality

To add new admin functionality for WhatsApp:

1. Add new AJAX action in `WhatsApp_Handler::__construct()`
2. Implement the handler method
3. The handler will be automatically registered

This modular approach allows each integration to manage its own admin functionality independently.

## Technical Notes

### Authentication Methods
1. **Phone Number + API Token**: For WhatsApp Business API
   - User provides phone number and API token
   - Token stored encrypted in database
   - Used for API authentication

2. **QR Code**: For WhatsApp Web/Desktop API
   - Generate QR code from API
   - User scans with WhatsApp mobile app
   - Session stored securely

3. **Webhook + Business ID**: For WhatsApp Cloud API
   - User provides webhook URL and Business ID
   - Webhook verified and registered
   - Events sent to webhook endpoint

### Webhook Events Structure
```json
{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "metadata": {
          "display_phone_number": "PHONE_NUMBER",
          "phone_number_id": "PHONE_NUMBER_ID"
        },
        "contacts": [...],
        "messages": [...],
        "statuses": [...]
      },
      "field": "messages"
    }]
  }]
}
```

### Credential Storage
- Store in `wp_aw_credentials` table
- Encrypt sensitive data (tokens, phone numbers)
- Support multiple instances (multiple WhatsApp accounts)
- Validate credentials before saving

### Error Codes Mapping
- `100`: Invalid phone number
- `101`: Authentication failed
- `102`: Rate limit exceeded
- `103`: Message sending failed
- `104`: Webhook verification failed
- `105`: Connection timeout
- `106`: Invalid media format
- `107`: Template not approved
- `108`: Group not found
- `109`: Contact not found

## Dependencies
- PHP cURL extension
- WordPress REST API
- Encryption functions (WordPress built-in)
- QR code generation library (optional, for QR code display)

## File Structure

```
wp-agentic-workflow/
├── configs/
│   ├── integrations/
│   │   └── whatsapp.json                    # WhatsApp integration config
│   ├── triggers/
│   │   ├── whatsapp-message-received.json
│   │   ├── whatsapp-message-sent.json
│   │   ├── whatsapp-message-delivered.json
│   │   ├── whatsapp-message-read.json
│   │   ├── whatsapp-message-failed.json
│   │   ├── whatsapp-contact-joined.json
│   │   ├── whatsapp-contact-left.json
│   │   ├── whatsapp-group-created.json
│   │   └── whatsapp-group-updated.json
│   └── tools/
│       ├── whatsapp-send-message.json
│       ├── whatsapp-send-media.json
│       ├── whatsapp-send-template.json
│       ├── whatsapp-reply-message.json
│       ├── whatsapp-mark-read.json
│       ├── whatsapp-get-contact.json
│       ├── whatsapp-create-group.json
│       ├── whatsapp-add-to-group.json
│       ├── whatsapp-remove-from-group.json
│       ├── whatsapp-get-group-info.json
│       ├── whatsapp-send-location.json
│       ├── whatsapp-send-contact.json
│       └── whatsapp-delete-message.json
├── includes/
│   └── integrations/
│       └── whatsapp/
│           ├── class-whatsapp-connection.php
│           ├── class-whatsapp-api-client.php
│           ├── class-whatsapp-error-handler.php
│           ├── triggers/
│           │   ├── class-message-received-trigger.php
│           │   ├── class-message-sent-trigger.php
│           │   ├── class-message-delivered-trigger.php
│           │   ├── class-message-read-trigger.php
│           │   ├── class-message-failed-trigger.php
│           │   ├── class-contact-joined-trigger.php
│           │   ├── class-contact-left-trigger.php
│           │   ├── class-group-created-trigger.php
│           │   └── class-group-updated-trigger.php
│           └── tools/
│               ├── class-send-message.php
│               ├── class-send-media.php
│               ├── class-send-template.php
│               ├── class-reply-message.php
│               ├── class-mark-read.php
│               ├── class-get-contact.php
│               ├── class-create-group.php
│               ├── class-add-to-group.php
│               ├── class-remove-from-group.php
│               ├── class-get-group-info.php
│               ├── class-send-location.php
│               ├── class-send-contact.php
│               └── class-delete-message.php
├── admin/
│   └── views/
│       └── whatsapp-settings.php
└── docs/
    └── integrations/
        └── whatsapp/
            ├── DEVELOPMENT_PLAN.md
            └── whatsapp.md
```

## Estimated Timeline
- **Week 1**: Integration config, settings UI, connection handler
- **Week 2**: Webhook handler, triggers implementation
- **Week 3**: Actions/tools implementation, API client
- **Week 4**: UI integration, error handling, testing, documentation

**Total: 4 weeks**

## Implementation Strategy

### Recommended Approach

1. **Start with Foundation** (Week 1)
   - Create integration configuration
   - Build settings UI
   - Implement connection handler
   - Test authentication methods

2. **Build Webhook Infrastructure** (Week 2)
   - Create webhook endpoint
   - Implement webhook parsing
   - Build trigger handlers
   - Test webhook events

3. **Implement Actions** (Week 3)
   - Create API client
   - Implement all action tools
   - Add error handling
   - Test message sending/receiving

4. **Polish & Integration** (Week 4)
   - Update UI components
   - Add node grouping
   - Complete documentation
   - Final testing

## Key Considerations

### 1. API Compatibility
- Support WhatsApp Business API
- Support WhatsApp Cloud API
- Handle API differences gracefully
- Provide clear error messages

### 2. Rate Limiting
- WhatsApp APIs have strict rate limits
- Implement rate limiting
- Queue messages when needed
- Provide user feedback on limits

### 3. Security
- Encrypt all tokens and credentials
- Verify webhook signatures
- Validate all inputs
- Sanitize outputs

### 4. Error Handling
- Handle connection failures
- Handle authentication errors
- Handle rate limit errors
- Provide user-friendly error messages

### 5. Testing
- Test with real WhatsApp accounts
- Test all authentication methods
- Test error scenarios
- Test rate limiting

## Success Metrics

- ✅ All authentication methods work reliably
- ✅ Webhook events trigger workflows correctly
- ✅ All actions execute successfully
- ✅ Error handling is robust
- ✅ UI is intuitive and user-friendly
- ✅ Documentation is complete
- ✅ Performance is acceptable
- ✅ Security is maintained

## Next Steps

1. Review and approve this plan
2. Set up WhatsApp Business API account (if using Cloud API)
3. Create test WhatsApp account
4. Begin Week 1 development
5. Set up development environment
6. Create GitHub issues/tasks for each week

---

## Resources

- [WhatsApp Business API Documentation](https://developers.facebook.com/docs/whatsapp)
- [WhatsApp Cloud API Documentation](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [WhatsApp Web API Documentation](https://github.com/pedroslopez/whatsapp-web.js)

