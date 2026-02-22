# MCP Solr Server

MCP (Model Context Protocol) server for Apache Solr in PHP. Manages Job and Company data from peviitor_core.

## Features

- **Procedural PHP** - No OOP, no external dependencies
- **JSON-RPC 2.0** - Standard protocol over STDIO
- **Authentication** - Basic Auth support for Solr
- **Docker Ready** - Runs in container

## Requirements

- PHP 8.2+
- Apache Solr
- cURL extension

## Quick Start

### Docker

```bash
# Build
docker build -t mcp-solr .

# Run
docker run -e SOLR_HOST=solr -e SOLR_PORT=8983 -e SOLR_USER=solr -e SOLR_PASS=SolrRocks mcp-solr
```

### Local

```bash
php mcp-server.php
```

## Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| SOLR_HOST | localhost | Solr hostname |
| SOLR_PORT | 8983 | Solr port |
| SOLR_USER | solr | Username |
| SOLR_PASS | SolrRocks | Password |
| SOLR_SCHEME | http | http or https |

## Available Methods

### Job Operations
- `job_select` - Search jobs with filters
- `job_search` - Simplified search
- `job_insert` / `job_index` - Insert job (url is uniquekey)
- `job_delete` - Delete by url
- `job_update` - Update fields by url
- `job_get` - Get by url

### Company Operations
- `company_select` - Search companies
- `company_search` - Simplified search
- `company_insert` / `company_index` - Insert company (id/CIF is uniquekey)
- `company_delete` - Delete by id
- `company_update` - Update fields by id
- `company_get` - Get by id

## Usage

```bash
# Send JSON-RPC request
echo '{"jsonrpc": "2.0", "method": "job_select", "params": {}, "id": 1}' | php mcp-server.php

# Insert a job
echo '{"jsonrpc": "2.0", "method": "job_insert", "params": {"url": "https://example.com/job/1", "title": "PHP Developer", "company": "Example SRL", "status": "scraped"}, "id": 1}' | php mcp-server.php

# Get a company
echo '{"jsonrpc": "2.0", "method": "company_get", "params": {"id": "12345678"}, "id": 1}' | php mcp-server.php
```

## Data Schemas

### Job
| Field | Type | Description |
|-------|------|-------------|
| url | string | Job URL (uniquekey) |
| title | string | Job title |
| company | string | Company name |
| cif | string | Company CIF |
| location | string[] | Locations |
| tags | string[] | Skills |
| workmode | string | remote/on-site/hybrid |
| status | string | scraped/tested/published/verified |
| salary | string | Salary range |

### Company
| Field | Type | Description |
|-------|------|-------------|
| id | string | CIF/CUI (uniquekey) |
| company | string | Company name |
| status | string | activ/suspendat/inactiv/radiat |
| location | string[] | Locations |
| website | string[] | Websites |
| career | string[] | Career pages |

## License

MIT
