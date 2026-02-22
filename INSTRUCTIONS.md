# MCP Server for Apache Solr - Project Instructions

## Project Status: ✅ COMPLETED

This is a fully functional MCP (Model Context Protocol) server for Apache Solr in PHP that handles job and company data from peviitor_core.

## Documentation Policy

**IMPORTANT:** Any changes to project files must be documented in this file (INSTRUCTIONS.md) and AGENTS.md.

When making changes to:
- PHP functions in `src/functions.php` or `mcp-server.php`
- Configuration (environment variables, Docker, OpenCode)
- Available operations or data models
- File structure

You MUST update the relevant documentation sections:
- **INSTRUCTIONS.md** - Project overview, operations, configuration
- **AGENTS.md** - Developer guidelines, code style, testing

## File Structure

```
mcp-solr/
├── src/
│   └── functions.php    # All PHP functions (no OOP)
├── mcp-server.php       # MCP server entry point (JSON-RPC 2.0 over STDIO)
├── Dockerfile           # PHP CLI Docker image
├── opencode.json        # OpenCode MCP configuration
└── AGENTS.md           # Developer guidelines
```

## Technology Stack

- **Language**: PHP (procedural, no OOP)
- **Server**: Apache Solr (MCP integration via STDIO)
- **No external dependencies** (pure PHP with cURL)

## Data Models

### Job Model (uniquekey: `url`)
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| url | string | Yes | Full URL to the job detail page. **unique** |
| title | string | Yes | Exact position title (max 200 chars, no HTML, trimmed) |
| company | string | No | Name of the hiring company (legal name, DIACRITICS REQUIRED) |
| cif | string | No | CIF/CUI of the company |
| location | string[] | No | Romanian cities/addresses (multi-valued array) |
| tags | string[] | No | Skills/education/experience tags (lowercase, max 20, NO DIACRITICS) |
| workmode | string | No | "remote", "on-site", "hybrid" |
| date | date | No | UTC ISO8601 timestamp of scrape |
| status | string | No | "scraped", "tested", "published", "verified" |
| vdate | date | No | Verified date (ISO8601) |
| expirationdate | date | No | Job expiration date (vdate + 30 days max) |
| salary | string | No | Format: "MIN-MAX CURRENCY" (e.g., "5000-8000 RON") |

### Company Model (uniquekey: `id`)
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string | Yes | CIF/CUI (exact 8 digits, no RO prefix) - **uniquekey** |
| company | string | Yes | Legal name from Trade Register (DIACRITICS REQUIRED) |
| status | string | No | "activ", "suspendat", "inactiv", "radiat" |
| location | string[] | No | Romanian cities/addresses (multi-valued) |
| website | string[] | No | Official company website (valid HTTP/HTTPS URL) |
| career | string[] | No | Official company career page (valid HTTP/HTTPS URL) |

## Available Operations

### Job Operations
- `job_select` - Search jobs with flexible queries
- `job_search` - Simplified search with common filters
- `job_index` / `job_insert` - Add new jobs (url is uniquekey)
- `job_delete` - Remove jobs by url
- `job_update` - Update job fields by url
- `job_get` - Get a single job by url

### Company Operations
- `company_select` - Search companies
- `company_search` - Simplified search with common filters
- `company_index` / `company_insert` - Add new companies (id/CIF is uniquekey)
- `company_delete` - Remove companies by id
- `company_update` - Update company fields by id
- `company_get` - Get a single company by id

## Configuration

Environment variables (with defaults):
- `SOLR_HOST` - Solr hostname (default: localhost)
- `SOLR_PORT` - Solr port (default: 8983)
- `SOLR_USER` - Solr username (default: solr)
- `SOLR_PASS` - Solr password (default: SolrRocks)
- `SOLR_SCHEME` - http or https (default: http)

## Docker Requirements

### Prerequisites
1. **Docker** must be running
2. **Solr container** must be running (e.g., `peviitor-solr`)
3. **mcp-solr image** must be built

### Build and Run
```bash
# Build the image
docker build -t mcp-solr .

# Check if Solr is running
docker ps | grep solr

# Start Solr if not running
docker start peviitor-solr

# Run with Docker (IMPORTANT: use -i flag for interactive STDIN)
docker run --rm -i -e SOLR_HOST=solr -e SOLR_PORT=8983 -e SOLR_USER=solr -e SOLR_PASS=SolrRocks mcp-solr

# Test with JSON-RPC request
echo '{"jsonrpc": "2.0", "method": "job_select", "params": {}, "id": 1}' | docker run --rm -i mcp-solr
```

### Important Notes
- MCP Solr connects to Solr at `solr:8983` (docker network)
- Without Solr running, MCP tools will return connection errors
- Keep Solr container running while using OpenCode with MCP Solr

## Local Development

```bash
# Syntax check
php -l src/functions.php
php -l mcp-server.php

# Run locally
php mcp-server.php

# Test manually (in another terminal)
echo '{"jsonrpc": "2.0", "method": "job_select", "params": {}, "id": 1}' | php mcp-server.php
```

## OpenCode Integration

The project includes `opencode.json` with MCP configuration:

```json
{
  "$schema": "https://opencode.ai/config.json",
  "mcp": {
    "mcp-solr": {
      "type": "local",
      "command": ["docker", "run", "--rm", "-i", "-e", "SOLR_HOST=solr", "-e", "SOLR_PORT=8983", "-e", "SOLR_USER=solr", "-e", "SOLR_PASS=SolrRocks", "mcp-solr"],
      "enabled": true
    }
  }
}
```

Note: `chrome-devtools` is defined globally in `~/.config/opencode/opencode.json` and does not need to be repeated here.

### Custom Commands

The project includes custom commands defined in `.opencode/commands/instructions.md`:
- `/instructions` - Shows project instructions and guidelines

Restart OpenCode after configuration changes.

## MCP Protocol Details

The server implements JSON-RPC 2.0 over STDIO:
- Reads JSON-RPC requests from STDIN
- Writes JSON-RPC responses to STDOUT
- Announces capabilities on startup (list of available tools)
- Supports all standard JSON-RPC error codes

Example request:
```json
{"jsonrpc": "2.0", "method": "job_get", "params": {"url": "https://example.com/job"}, "id": 1}
```

Example response:
```json
{"jsonrpc": "2.0", "id": 1, "result": {...}}
```

## Changelog

### 2026-02-22
- Created `opencode.json` with MCP configuration for mcp-solr
- Updated `.opencode/commands/instructions.md` to use `opencode/big-pickle` model
- Added Docker requirements section to INSTRUCTIONS.md and AGENTS.md
- Started Solr container (`peviitor-solr`) for MCP connectivity
- Configured `chrome-devtools` globally in `~/.config/opencode/opencode.json`
