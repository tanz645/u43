# Integrations

This document provides an overview of integrations. For detailed documentation on each integration, see the [integrations directory](integrations/).

## Quick Navigation

### WordPress Integrations
- **[WordPress Core](integrations/wordpress/wordpress.md)** - Hook-based triggers, events, and filters
- **[WooCommerce](integrations/wordpress/woocommerce.md)** - E-commerce integration
- **[WPForms](integrations/wordpress/wpforms.md)** - Form submission and management
- **[Contact Form 7](integrations/wordpress/contact-form-7.md)** - Form integration

### Analytics Integrations
- **[Site Kit Google Analytics](integrations/analytics/site-kit.md)** - Analytics and reporting

### Collaboration Tools
- **[Slack](integrations/collaboration/slack.md)** - Team communication and notifications
- **[WhatsApp](integrations/whatsapp/index.md)** - WhatsApp messaging and automation (🚧 In Development)

### Google Integrations
- **[Google APIs](integrations/google/index.md)** - Drive, Docs, Sheets, Calendar, Gmail integration (🚧 In Development)
  - See [Development Plan](integrations/google/DEVELOPMENT_PLAN.md) for phased implementation roadmap

### Project Management Tools
- **[Jira](integrations/project-management/jira.md)** - Issue tracking and project management
- **[Trello](integrations/project-management/trello.md)** - Board and card management
- **[ClickUp](integrations/project-management/clickup.md)** - Task and workspace management
- **[Monday.com](integrations/project-management/monday.md)** - Board and item management
- **[Asana](integrations/project-management/asana.md)** - Task and project management

### Database Integrations

**Relational Databases:**
- **[PostgreSQL](integrations/databases/postgresql.md)** - Advanced relational database with pgvector support
- **[MySQL/MariaDB](integrations/databases/mysql.md)** - Popular relational databases
- **[SQLite](integrations/databases/sqlite.md)** - Lightweight file-based database

**NoSQL Databases:**
- **[MongoDB](integrations/databases/mongodb.md)** - NoSQL document database

**Time-Series Databases:**
- **[ClickHouse](integrations/databases/clickhouse.md)** - Column-oriented analytical database
- **[TimescaleDB](integrations/databases/timescaledb.md)** - Time-series database built on PostgreSQL

**Search & Cache:**
- **[Redis](integrations/databases/redis.md)** - In-memory cache and data store
- **[Elasticsearch](integrations/databases/elasticsearch.md)** - Search and analytics engine

### SEO Integrations
- **[All in One SEO](integrations/seo/all-in-one-seo.md)** - SEO metadata and sitemap management

### Email Integrations
- **[WP Mail SMTP](integrations/email/wp-mail-smtp.md)** - Reliable email sending via SMTP

### Marketing Integrations
- **[HubSpot](integrations/marketing/hubspot.md)** - CRM and marketing automation
- **[PushEngage](integrations/marketing/pushengage.md)** - Push notifications

### E-commerce Extensions
- **[Wholesale Suite](integrations/ecommerce/wholesale-suite.md)** - B2B wholesale functionality
- **[Advanced Coupons](integrations/ecommerce/advanced-coupons.md)** - Advanced coupon management

### Security Integrations
- **[Sucuri](integrations/security/sucuri.md)** - Security monitoring and threat detection

### Performance Integrations
- **[W3 Total Cache](integrations/performance/w3-total-cache.md)** - Caching and performance optimization

### Content Integrations
- **[Advanced Custom Fields](integrations/content/advanced-custom-fields.md)** - Custom field management

### Utilities
- **[Redirection](integrations/utilities/redirection.md)** - URL redirect management
- **[Pretty Links](integrations/utilities/pretty-links.md)** - Short link management

### Backup Integrations
- **[UpdraftPlus](integrations/backup/updraftplus.md)** - Backup and restore operations

### Analytics & Feedback
- **[UserFeedback](integrations/analytics/userfeedback.md)** - Survey and feedback collection

### Integration Guides
- **[Best Practices](integrations/guides/best-practices.md)** - Integration development guidelines
- **[Developer Hooks](integrations/guides/developer-hooks.md)** - Hooks and filters for developers
- **[Adding Custom Integrations](integrations/guides/adding-integrations.md)** - Guide to creating your own integrations

### AI & Vector Stores
- **[RAG & Vector Stores](../RAG_VECTOR_STORES.md)** - Retrieval-Augmented Generation and vector database integrations

## Integration Overview

All integrations follow the same configuration pattern:

1. **Configuration Files**: JSON files define integrations, tools, and triggers
2. **Handler Classes**: PHP classes implement the business logic
3. **Auto-Discovery**: Configurations are automatically discovered and registered
4. **Consistent Pattern**: Same structure across all integrations
5. **Modular Admin Handlers**: Each integration can have its own admin handler class for AJAX requests and admin-specific functionality

## Admin Handler Architecture

The plugin uses a modular handler architecture for admin-side functionality:

- **Location**: `/admin/handlers/class-{integration}-handler.php`
- **Namespace**: `U43\Admin\Handlers\{Integration}_Handler`
- **Auto-Loading**: Handlers are automatically discovered and loaded
- **Pattern**: Follow the naming convention `class-{integration}-handler.php`

### Example Handler Structure

```php
namespace U43\Admin\Handlers;

class WhatsApp_Handler {
    public function __construct() {
        // Register AJAX actions
        add_action('wp_ajax_u43_test_whatsapp_connection', [$this, 'ajax_test_connection']);
    }
    
    public function ajax_test_connection() {
        // Handler implementation
    }
}
```

This modular approach allows each integration to manage its own admin functionality independently, making the codebase more scalable and maintainable.

## Configuration Compatibility

The configuration system is **plugin-agnostic** - it works with:
- ✅ **Hook-based plugins** (WooCommerce, Contact Form 7, WPForms)
- ✅ **API-based plugins** (Site Kit, external services)
- ✅ **OAuth authentication** (Google, Facebook, Slack, etc.)
- ✅ **Scheduled/cron-based triggers** (analytics thresholds, reports)
- ✅ **Real-time data** (realtime analytics, webhooks)
- ✅ **Form plugins** (WPForms, Contact Form 7, Gravity Forms, etc.)
- ✅ **Project Management Tools** (Jira, Trello, ClickUp, Monday.com, Asana)
- ✅ **Collaboration Tools** (Slack, Microsoft Teams, Discord)
- ✅ **Databases** (PostgreSQL, MySQL, MongoDB, ClickHouse, TimescaleDB, Redis, Elasticsearch, SQLite)
- ✅ **SEO Tools** (All in One SEO, Yoast SEO, Rank Math)
- ✅ **Email Tools** (WP Mail SMTP, Post SMTP)
- ✅ **Security Tools** (Sucuri, Wordfence, iThemes Security)
- ✅ **Performance Tools** (W3 Total Cache, WP Rocket, Autoptimize)
- ✅ **Marketing Tools** (HubSpot, PushEngage, OptinMonster)
- ✅ **Content Tools** (Advanced Custom Fields, Redirection, Pretty Links)

## Quick Start

1. Choose an integration from the category above
2. Follow the integration-specific guide
3. Create configuration files
4. Implement handler classes
5. Test in workflows

## Integration Methods

There are three primary ways for integrations to work:

1. **Configuration-Based Integration** (Recommended): Add configuration files that are automatically discovered
2. **Programmatic Integration**: Use PHP hooks and filters to register components
3. **Hybrid Approach**: Combine configuration files with custom PHP handlers

For detailed examples and complete documentation, see the [integrations directory](integrations/).

