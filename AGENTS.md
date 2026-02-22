# AGENTS.md - Developer Guidelines for MCP Solr Project

## Project Overview
This is an MCP (Model Context Protocol) server for Apache Solr in PHP. It manages Job and Company data from peviitor_core.

## Quick Start
```bash
# Build Docker image
docker build -t mcp-solr .

# Run MCP server
docker run -e SOLR_HOST=solr -e SOLR_PORT=8983 -e SOLR_USER=solr -e SOLR_PASS=SolrRocks mcp-solr

# Run locally (requires PHP 8.2+)
php mcp-server.php

# Syntax check
php -l src/functions.php
php -l mcp-server.php
```

## Configuration
Environment variables:
- `SOLR_HOST` - Solr host (default: localhost)
- `SOLR_PORT` - Solr port (default: 8983)
- `SOLR_USER` - Solr username (default: solr)
- `SOLR_PASS` - Solr password (default: SolrRocks)
- `SOLR_SCHEME` - http or https (default: http)

## Code Style Guidelines

### General Principles
- Procedural PHP (no OOP classes)
- Simple functions, no namespaces
- Keep functions small and focused
- Self-documenting code

### Naming Conventions
- Functions: snake_case (e.g., `job_select`, `company_delete`)
- Constants: SCREAMING_SNAKE_CASE
- Variables: snake_case

### Formatting
- 4 spaces indentation
- Max 120 characters per line
- Opening brace on same line for functions
- Opening brace on new line for control structures

### Error Handling
- Return `['error' => 'message']` on errors
- Validate required parameters
- Use try/catch for exceptions

## Available Functions

### Core Functions
- `solr_request($core, $method, $endpoint, $data)` - HTTP client for Solr
- `solr_escape($value)` - Escape value for Solr queries

### Job Functions
- `job_select($query, $start, $rows, $filters)` - Search jobs
- `job_search($term, $filters)` - Simplified search
- `job_insert($data)` - Insert job
- `job_index($data)` - Alias for insert
- `job_delete($url)` - Delete job by url
- `job_update($url, $fields)` - Update job
- `job_get($url)` - Get job by url

### Company Functions
- `company_select($query, $start, $rows, $filters)` - Search companies
- `company_search($term, $filters)` - Simplified search
- `company_insert($data)` - Insert company
- `company_index($data)` - Alias for insert
- `company_delete($id)` - Delete company by id
- `company_update($id, $fields)` - Update company
- `company_get($id)` - Get company by id

## File Structure
```
src/
└── functions.php    # All PHP functions (no OOP)

mcp-server.php       # MCP server entry point
Dockerfile           # PHP CLI Docker image
```

## MCP Protocol

The server implements JSON-RPC 2.0 over STDIO:

- Reads JSON-RPC requests from STDIN
- Writes JSON-RPC responses to STDOUT
- Announces capabilities on startup
- Supports all standard JSON-RPC error codes

Example request:
```json
{"jsonrpc": "2.0", "method": "job_get", "params": {"url": "https://example.com/job"}, "id": 1}
```

Example response:
```json
{"jsonrpc": "2.0", "id": 1, "result": {...}}
```

## Security
- Never log sensitive data (passwords, tokens)
- Validate all inputs
- Escape query values using `solr_escape()` function
- Do NOT use `escapeshellarg()` for Solr queries

## Testing

Run syntax checks:
```bash
php -l src/functions.php
php -l mcp-server.php
```

Test manually:
```bash
# Start server in background
php mcp-server.php &

# Send JSON-RPC request
echo '{"jsonrpc": "2.0", "method": "job_select", "params": {}, "id": 1}' | php mcp-server.php
```

## Docker Build

```bash
# Build the image
docker build -t mcp-solr .

# Run with custom Solr connection
docker run -e SOLR_HOST=my-solr -e SOLR_USER=admin -e SOLR_PASS=secret mcp-solr

# Connect to Solr on host network
docker run --network host mcp-solr
```
