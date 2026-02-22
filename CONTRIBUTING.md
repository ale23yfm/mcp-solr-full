# Contributing to MCP Solr Server

Thank you for your interest in contributing to MCP Solr Server!

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported
2. Create a new issue with:
   - Clear title describing the issue
   - Steps to reproduce
   - Expected vs actual behavior
   - PHP version, OS information

### Suggesting Features

1. Check existing issues and PRs
2. Create a new issue with:
   - Clear title describing the feature
   - Detailed description
   - Use cases

### Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Make your changes
4. Run syntax checks:
   ```bash
   php -l src/functions.php
   php -l mcp-server.php
   ```
5. Commit with clear messages
6. Push to your fork
7. Submit a pull request

## Code Style

- Procedural PHP (no OOP classes)
- 4 spaces indentation
- snake_case for functions and variables
- Add comments in English
- Max 120 characters per line

## Testing

Test your changes manually:
```bash
php mcp-server.php
# Send test requests via STDIN
```

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
