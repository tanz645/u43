# Development Guide

## Prerequisites

- WordPress 5.8+
- PHP 7.4+
- Composer (for dependencies)
- Node.js & npm (for frontend assets)

## Setup

1. Clone the repository
2. Run `composer install`
3. Run `npm install`
4. Activate the plugin in WordPress admin

## Adding Configurations

1. Place configuration files in appropriate `configs/` subdirectories
2. Ensure JSON schema validation passes
3. Create corresponding handler classes
4. Test configuration loading

## Testing

```bash
# Run unit tests
composer test

# Run integration tests
composer test:integration
```

## Code Standards

- Follow WordPress Coding Standards
- Use PSR-4 autoloading
- Write PHPDoc comments
- Follow semantic versioning

## Database Schema

### Tables

- `wp_aw_flows` - Stores workflow definitions
- `wp_aw_flow_executions` - Execution history and logs
- `wp_aw_flow_nodes` - Individual nodes in flows
- `wp_aw_flow_edges` - Connections between nodes
- `wp_aw_integrations` - Integration credentials and settings
- `wp_aw_execution_logs` - Detailed execution logs

## Security Considerations

- All user inputs are sanitized and validated
- Capability checks for all operations
- Nonce verification for admin actions
- Secure storage of integration credentials
- Rate limiting for API endpoints
- SQL injection prevention via prepared statements

## Performance Optimization

- Configuration caching
- Execution queue for long-running workflows
- Database query optimization
- Asset minification and concatenation
- Lazy loading of configurations
- Background job processing

## Roadmap

### Phase 1: Core Foundation
- [ ] Basic plugin structure
- [ ] Configuration system
- [ ] Registry system
- [ ] Flow manager
- [ ] Basic executor engine

### Phase 2: WordPress Integration
- [ ] Hook-based triggers
- [ ] Event system
- [ ] Filter integration
- [ ] Built-in WordPress tools

### Phase 3: Visual Builder
- [ ] Drag-and-drop interface
- [ ] Node editor
- [ ] Flow canvas
- [ ] Real-time execution preview

### Phase 4: Agents & AI
- [ ] Agent framework
- [ ] LLM integration
- [ ] Decision-making agents
- [ ] Learning capabilities

### Phase 5: Integrations
- [ ] OAuth2 authentication
- [ ] Popular service integrations
- [ ] Custom integration builder
- [ ] Webhook support

### Phase 6: Advanced Features
- [ ] Workflow templates
- [ ] Conditional logic
- [ ] Loops and iterations
- [ ] Error handling and retries
- [ ] Workflow analytics

## Contributing

Contributions are welcome! Please read our contributing guidelines before submitting pull requests.

### Contribution Guidelines

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Write tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

### Code Review Process

1. All pull requests require review
2. Code must pass CI checks
3. Documentation must be updated
4. Tests must be included

## License

GPL v2 or later

## Support

For support, please open an issue on GitHub or contact the development team.

