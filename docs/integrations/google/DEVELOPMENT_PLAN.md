# Google APIs Integration - Development Plan

## Overview

This document outlines a gradual development plan for integrating Google APIs (Docs, Drive, Sheets, Calendar, Gmail, etc.) into the WordPress Agentic Workflow plugin. The plan follows the existing architecture patterns and builds incrementally.

## Prerequisites

- WordPress Agentic Workflow plugin architecture in place
- Credential Manager with OAuth2 support (already exists)
- Integration configuration system (already exists)
- Tool and trigger registry system (already exists)

## Development Phases

---

## Phase 1: Foundation - Google OAuth2 Setup
**Goal**: Establish OAuth2 authentication infrastructure for Google APIs  
**Duration**: 1-2 weeks  
**Priority**: Critical (required for all Google APIs)

### Tasks

1. **Create Base Google Integration Configuration**
   - Create `configs/integrations/google.json`
   - Configure OAuth2 endpoints:
     - Authorization URL: `https://accounts.google.com/o/oauth2/v2/auth`
     - Token URL: `https://oauth2.googleapis.com/token`
     - Revoke URL: `https://oauth2.googleapis.com/revoke`
   - Define common scopes structure
   - Set up redirect URI handling

2. **Implement Google OAuth Handler**
   - Create `includes/integrations/google/class-google-oauth-handler.php`
   - Extend base OAuth handler
   - Implement Google-specific OAuth flow:
     - Authorization code exchange
     - Token refresh (Google tokens expire)
     - Token revocation
   - Handle Google's consent screen
   - Support incremental authorization (add scopes later)

3. **Create Credential Management UI**
   - Add Google integration settings page
   - OAuth connection button
   - Scope selection interface
   - Token status display (valid/expired)
   - Multiple account support (instance management)

4. **Test OAuth Flow**
   - Test authorization flow
   - Test token refresh
   - Test token revocation
   - Test multiple accounts

### Deliverables
- ✅ Google OAuth2 integration config
- ✅ Working OAuth flow
- ✅ Admin UI for connecting Google accounts
- ✅ Token management (refresh, revoke)

### Configuration Example
```json
{
  "id": "google",
  "name": "Google",
  "description": "Google APIs integration (Drive, Docs, Sheets, etc.)",
  "version": "1.0.0",
  "icon": "google",
  "authentication": {
    "type": "oauth2",
    "authorization_url": "https://accounts.google.com/o/oauth2/v2/auth",
    "token_url": "https://oauth2.googleapis.com/token",
    "revoke_url": "https://oauth2.googleapis.com/revoke",
    "scopes": [],
    "client_id_field": "client_id",
    "client_secret_field": "client_secret",
    "storage": "encrypted"
  },
  "inputs": {
    "client_id": {
      "type": "string",
      "required": true,
      "label": "Google Client ID",
      "sensitive": false,
      "description": "From Google Cloud Console"
    },
    "client_secret": {
      "type": "string",
      "required": true,
      "label": "Google Client Secret",
      "sensitive": true,
      "description": "From Google Cloud Console"
    }
  }
}
```

---

## Phase 2: Google Drive - Basic Operations
**Goal**: Enable basic file operations in Google Drive  
**Duration**: 2-3 weeks  
**Priority**: High (most commonly requested)

### Tasks

1. **Create Drive Integration Config**
   - Create `configs/integrations/google-drive.json`
   - Extend base Google config
   - Add Drive-specific scopes:
     - `https://www.googleapis.com/auth/drive.file` (create/access user files)
     - `https://www.googleapis.com/auth/drive.readonly` (read-only access)
   - Define Drive API base URL: `https://www.googleapis.com/drive/v3`

2. **Implement Core Drive Tools**
   - **List Files** (`google_drive_list_files`)
     - List files in folder
     - Filter by type, name, modified date
     - Pagination support
   - **Get File** (`google_drive_get_file`)
     - Get file metadata
     - Get file content
     - Download file
   - **Upload File** (`google_drive_upload_file`)
     - Upload new file
     - Support different file types
     - Set folder location
   - **Create Folder** (`google_drive_create_folder`)
     - Create new folder
     - Set parent folder
   - **Delete File** (`google_drive_delete_file`)
     - Delete file or folder
     - Move to trash option

3. **Create Tool Configurations**
   - Create tool configs in `configs/tools/`
   - Define inputs/outputs for each tool
   - Add validation rules
   - Create handler class mappings

4. **Implement Handler Classes**
   - Create `includes/integrations/google/drive/tools/` directory
   - Implement each tool handler:
     - `class-list-files.php`
     - `class-get-file.php`
     - `class-upload-file.php`
     - `class-create-folder.php`
     - `class-delete-file.php`
   - Use Google Drive API v3
   - Handle errors gracefully
   - Implement rate limiting

5. **Add Drive Triggers** (Optional for Phase 2)
   - File created trigger
   - File modified trigger
   - File deleted trigger

### Deliverables
- ✅ 5-6 core Drive tools working
- ✅ File upload/download functionality
- ✅ Folder management
- ✅ Integration with workflow nodes

### Example Tool: Upload File
```json
{
  "id": "google_drive_upload_file",
  "name": "Upload File to Google Drive",
  "description": "Uploads a file to Google Drive",
  "version": "1.0.0",
  "category": "google",
  "icon": "google-drive",
  "integration": "google_drive",
  "inputs": {
    "file_path": {
      "type": "string",
      "required": true,
      "label": "File Path",
      "description": "Local file path or URL to upload"
    },
    "folder_id": {
      "type": "string",
      "required": false,
      "label": "Folder ID",
      "description": "ID of folder to upload to (optional)"
    },
    "file_name": {
      "type": "string",
      "required": false,
      "label": "File Name",
      "description": "Custom file name (optional)"
    }
  },
  "outputs": {
    "file_id": {
      "type": "string",
      "label": "File ID"
    },
    "file_url": {
      "type": "string",
      "label": "File URL"
    },
    "web_view_link": {
      "type": "string",
      "label": "Web View Link"
    }
  },
  "handler": "Integrations\\Google\\Drive\\Tools\\UploadFile"
}
```

---

## Phase 3: Google Docs - Document Operations
**Goal**: Enable document creation and manipulation  
**Duration**: 2-3 weeks  
**Priority**: High

### Tasks

1. **Create Docs Integration Config**
   - Create `configs/integrations/google-docs.json`
   - Add Docs API scopes:
     - `https://www.googleapis.com/auth/documents` (read/write)
     - `https://www.googleapis.com/auth/documents.readonly` (read-only)
   - Define Docs API base URL: `https://docs.googleapis.com/v1`

2. **Implement Core Docs Tools**
   - **Create Document** (`google_docs_create_document`)
     - Create new document
     - Set title
     - Return document ID
   - **Get Document** (`google_docs_get_document`)
     - Get document content
     - Get document metadata
   - **Update Document** (`google_docs_update_document`)
     - Insert text
     - Format text
     - Add images
     - Batch requests support
   - **Append Text** (`google_docs_append_text`)
     - Simple text append
     - Formatting options
   - **Export Document** (`google_docs_export_document`)
     - Export as PDF
     - Export as Word
     - Export as HTML

3. **Create Tool Configurations**
   - Define all Docs tool configs
   - Handle complex inputs (batch requests, formatting)

4. **Implement Handler Classes**
   - Create `includes/integrations/google/docs/tools/` directory
   - Implement Docs API v1 integration
   - Handle batch requests efficiently
   - Support rich text formatting

### Deliverables
- ✅ Document creation
- ✅ Document reading
- ✅ Document editing (text insertion, formatting)
- ✅ Document export

---

## Phase 4: Google Sheets - Spreadsheet Operations
**Goal**: Enable spreadsheet operations  
**Duration**: 2-3 weeks  
**Priority**: Medium-High

### Tasks

1. **Create Sheets Integration Config**
   - Create `configs/integrations/google-sheets.json`
   - Add Sheets API scopes:
     - `https://www.googleapis.com/auth/spreadsheets` (read/write)
     - `https://www.googleapis.com/auth/spreadsheets.readonly` (read-only)
   - Define Sheets API base URL: `https://sheets.googleapis.com/v4`

2. **Implement Core Sheets Tools**
   - **Create Spreadsheet** (`google_sheets_create_spreadsheet`)
   - **Get Spreadsheet** (`google_sheets_get_spreadsheet`)
   - **Read Range** (`google_sheets_read_range`)
   - **Write Range** (`google_sheets_write_range`)
   - **Append Row** (`google_sheets_append_row`)
   - **Update Cell** (`google_sheets_update_cell`)
   - **Clear Range** (`google_sheets_clear_range`)
   - **Batch Update** (`google_sheets_batch_update`)

3. **Implement Handler Classes**
   - Create `includes/integrations/google/sheets/tools/` directory
   - Implement Sheets API v4 integration
   - Handle A1 notation
   - Support batch operations

### Deliverables
- ✅ Spreadsheet creation
- ✅ Read/write operations
- ✅ Range operations
- ✅ Batch updates

---

## Phase 5: Advanced Features & Additional APIs
**Goal**: Add advanced features and more Google APIs  
**Duration**: 3-4 weeks  
**Priority**: Medium

### Tasks

1. **Google Calendar Integration**
   - Create events
   - List events
   - Update events
   - Delete events
   - Calendar triggers (event created, event updated)

2. **Gmail Integration**
   - Send email
   - Read emails
   - Search emails
   - Gmail triggers (new email received)

3. **Google Forms Integration**
   - Create form
   - Get form responses
   - Form submission triggers

4. **Advanced Drive Features**
   - File sharing/permissions
   - File versioning
   - Drive triggers (webhooks)

5. **Batch Operations**
   - Batch API requests
   - Rate limiting
   - Error handling

6. **Search & Filtering**
   - Advanced file search
   - Query builders
   - Filtering options

### Deliverables
- ✅ Calendar integration
- ✅ Gmail integration
- ✅ Forms integration
- ✅ Advanced Drive features
- ✅ Batch operations support

---

## Phase 6: Triggers & Webhooks
**Goal**: Enable real-time triggers from Google services  
**Duration**: 2-3 weeks  
**Priority**: Medium

### Tasks

1. **Google Drive Triggers**
   - File created trigger
   - File modified trigger
   - File deleted trigger
   - Implement Google Drive webhooks (Push notifications)

2. **Google Calendar Triggers**
   - Event created trigger
   - Event updated trigger
   - Event deleted trigger
   - Calendar webhook setup

3. **Gmail Triggers**
   - New email received trigger
   - Email label changed trigger
   - Gmail push notifications

4. **Webhook Infrastructure**
   - Webhook endpoint registration
   - Webhook verification (Google requires verification)
   - Webhook security
   - Webhook retry logic

### Deliverables
- ✅ Real-time triggers for major services
- ✅ Webhook infrastructure
- ✅ Trigger configuration UI

---

## Phase 7: Testing & Documentation
**Goal**: Comprehensive testing and documentation  
**Duration**: 1-2 weeks  
**Priority**: High

### Tasks

1. **Unit Tests**
   - Test each tool handler
   - Test OAuth flow
   - Test error handling
   - Test edge cases

2. **Integration Tests**
   - Test full workflows
   - Test multi-step operations
   - Test error recovery
   - Test rate limiting

3. **Documentation**
   - Create integration guide: `docs/integrations/google/index.md`
   - Create API-specific guides:
     - `docs/integrations/google/drive.md`
     - `docs/integrations/google/docs.md`
     - `docs/integrations/google/sheets.md`
     - `docs/integrations/google/calendar.md`
     - `docs/integrations/google/gmail.md`
   - Add example workflows
   - Create video tutorials (optional)

4. **User Documentation**
   - Setup guide
   - OAuth configuration guide
   - Common use cases
   - Troubleshooting guide

### Deliverables
- ✅ Comprehensive test suite
- ✅ Complete documentation
- ✅ Example workflows
- ✅ Setup guides

---

## Phase 8: Optimization & Polish
**Goal**: Performance optimization and UX improvements  
**Duration**: 1-2 weeks  
**Priority**: Low-Medium

### Tasks

1. **Performance Optimization**
   - Implement caching for API responses
   - Batch API calls where possible
   - Optimize token refresh
   - Reduce API quota usage

2. **Error Handling Improvements**
   - Better error messages
   - Retry logic with exponential backoff
   - User-friendly error notifications
   - Error logging and monitoring

3. **UX Improvements**
   - Better admin UI
   - File picker for Drive
   - Document preview
   - Progress indicators

4. **Security Enhancements**
   - Scope minimization
   - Token rotation
   - Security audit
   - Rate limiting per user

### Deliverables
- ✅ Optimized performance
- ✅ Improved error handling
- ✅ Enhanced UX
- ✅ Security improvements

---

## Implementation Strategy

### Recommended Approach

1. **Start with Phase 1** (OAuth Foundation)
   - This is critical for all Google APIs
   - Test thoroughly before moving on

2. **Choose One API to Start** (Phase 2 or 3)
   - Recommend starting with **Google Drive** (Phase 2)
   - It's the most versatile and commonly used
   - Provides foundation for Docs/Sheets (they're stored in Drive)

3. **Build Incrementally**
   - Complete one phase before starting the next
   - Test each phase thoroughly
   - Get user feedback early

4. **Parallel Development** (After Phase 2)
   - Once Drive is working, Docs and Sheets can be developed in parallel
   - They share the same OAuth infrastructure

### Development Order Recommendation

```
Phase 1: OAuth Foundation
    ↓
Phase 2: Google Drive (Basic)
    ↓
Phase 3: Google Docs (can start in parallel with Phase 4)
    ↓
Phase 4: Google Sheets (can start in parallel with Phase 3)
    ↓
Phase 5: Advanced Features & Additional APIs
    ↓
Phase 6: Triggers & Webhooks
    ↓
Phase 7: Testing & Documentation
    ↓
Phase 8: Optimization & Polish
```

## File Structure

```
wp-agentic-workflow/
├── configs/
│   ├── integrations/
│   │   ├── google.json                    # Base Google OAuth config
│   │   ├── google-drive.json             # Drive integration
│   │   ├── google-docs.json              # Docs integration
│   │   ├── google-sheets.json            # Sheets integration
│   │   ├── google-calendar.json          # Calendar integration
│   │   └── google-gmail.json             # Gmail integration
│   └── tools/
│       ├── google-drive-*.json           # Drive tools
│       ├── google-docs-*.json            # Docs tools
│       ├── google-sheets-*.json          # Sheets tools
│       ├── google-calendar-*.json        # Calendar tools
│       └── google-gmail-*.json           # Gmail tools
├── includes/
│   └── integrations/
│       └── google/
│           ├── class-google-oauth-handler.php
│           ├── class-google-api-client.php    # Shared API client
│           ├── drive/
│           │   └── tools/
│           │       ├── class-list-files.php
│           │       ├── class-upload-file.php
│           │       └── ...
│           ├── docs/
│           │   └── tools/
│           │       ├── class-create-document.php
│           │       └── ...
│           ├── sheets/
│           │   └── tools/
│           │       └── ...
│           ├── calendar/
│           │   └── tools/
│           │       └── ...
│           └── gmail/
│               └── tools/
│                   └── ...
└── docs/
    └── integrations/
        └── google/
            ├── index.md
            ├── drive.md
            ├── docs.md
            ├── sheets.md
            ├── calendar.md
            └── gmail.md
```

## Key Considerations

### 1. OAuth Scopes
- Request minimal scopes initially
- Support incremental authorization
- Allow users to add scopes as needed

### 2. API Quotas
- Google APIs have rate limits
- Implement rate limiting
- Cache responses when appropriate
- Batch requests when possible

### 3. Error Handling
- Handle token expiration
- Handle rate limit errors
- Handle quota exceeded errors
- Provide user-friendly error messages

### 4. Security
- Encrypt all tokens
- Secure OAuth redirect URIs
- Validate webhook signatures
- Implement CSRF protection

### 5. Testing
- Use Google API test accounts
- Test with different permission levels
- Test error scenarios
- Test rate limiting

## Success Metrics

- ✅ OAuth flow works reliably
- ✅ All core tools function correctly
- ✅ Error handling is robust
- ✅ Documentation is complete
- ✅ Users can successfully create workflows
- ✅ Performance is acceptable
- ✅ Security is maintained

## Timeline Estimate

**Total Duration**: 12-18 weeks (3-4.5 months)

- Phase 1: 1-2 weeks
- Phase 2: 2-3 weeks
- Phase 3: 2-3 weeks
- Phase 4: 2-3 weeks
- Phase 5: 3-4 weeks
- Phase 6: 2-3 weeks
- Phase 7: 1-2 weeks
- Phase 8: 1-2 weeks

**Note**: Phases 3 and 4 can be developed in parallel after Phase 2 is complete, reducing total time.

## Next Steps

1. Review and approve this plan
2. Set up Google Cloud Console project
3. Create OAuth credentials
4. Begin Phase 1 development
5. Set up development environment
6. Create GitHub issues/tasks for each phase

---

## Resources

- [Google API PHP Client Library](https://github.com/googleapis/google-api-php-client)
- [Google Drive API Documentation](https://developers.google.com/drive/api)
- [Google Docs API Documentation](https://developers.google.com/docs/api)
- [Google Sheets API Documentation](https://developers.google.com/sheets/api)
- [Google OAuth2 Documentation](https://developers.google.com/identity/protocols/oauth2)

