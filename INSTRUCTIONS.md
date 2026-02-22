# MCP Server for Apache Solr - Project Instructions

## Project Overview

We are developing an MCP (Model Context Protocol) server for Apache Solr in PHP that will handle job and company data from the peviitor_core project.

## Core Requirements

### Technology Stack
- **Language**: PHP (procedural, no OOP)
- **Server**: Apache Solr (MCP integration via STDIO)
- **No external dependencies** (pure PHP with cURL)

### Data Models

#### Job Model (uniquekey: `url`)
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

#### Company Model (uniquekey: `id`)
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string | Yes | CIF/CUI (exact 8 digits, no RO prefix) - **uniquekey** |
| company | string | Yes | Legal name from Trade Register (DIACRITICS REQUIRED) |
| status | string | No | "activ", "suspendat", "inactiv", "radiat" |
| location | string[] | No | Romanian cities/addresses (multi-valued) |
| website | string[] | No | Official company website (valid HTTP/HTTPS URL) |
| career | string[] | No | Official company career page (valid HTTP/HTTPS URL) |

### Required Operations

Implement the following CRUD operations for both:

1. ** Job and Company modelsSelect** - Query/search documents in Solr
2. **Index** - Add new documents to Solr
3. **Insert** - Alias for Index (add documents)
4. **Delete** - Remove documents from Solr by uniquekey
5. **Update** - Update existing documents in Solr
6. **Get** - Get single document by uniquekey

## Prompt

Create an MCP server in PHP (procedural, no OOP, no external dependencies) that interfaces with Apache Solr to manage Job and Company data from peviitor_core. The server should:

- Use pure PHP with cURL for HTTP requests
- Expose a JSON-RPC 2.0 interface over STDIO
- Support authentication with Solr (username/password)
- Read configuration from environment variables

The server should expose tools for:

- **Job Operations**:
  - `job_select` - Search jobs with flexible queries (filter by title, company, location, tags, workmode, status, date range, salary range)
  - `job_search` - Simplified search with common filters
  - `job_index` / `job_insert` - Add new jobs to Solr (url is the uniquekey)
  - `job_delete` - Remove jobs by url
  - `job_update` - Update job fields by url
  - `job_get` - Get a single job by url

- **Company Operations**:
  - `company_select` - Search companies (filter by id, company name, status, location)
  - `company_search` - Simplified search with common filters
  - `company_index` / `company_insert` - Add new companies to Solr (id/CIF is the uniquekey)
  - `company_delete` - Remove companies by id
  - `company_update` - Update company fields by id
  - `company_get` - Get a single company by id

Use the schemas provided above. Handle multi-valued arrays (string[]) correctly for Solr. Ensure proper error handling and validation. The server should follow MCP protocol specifications.

## Configuration

Environment variables (with defaults):
- `SOLR_HOST` - Solr hostname (default: localhost)
- `SOLR_PORT` - Solr port (default: 8983)
- `SOLR_USER` - Solr username (default: solr)
- `SOLR_PASS` - Solr password (default: SolrRocks)
- `SOLR_SCHEME` - http or https (default: http)

## Docker

Build and run:
```bash
docker build -t mcp-solr .
docker run -e SOLR_HOST=solr -e SOLR_PORT=8983 -e SOLR_USER=solr -e SOLR_PASS=SolrRocks mcp-solr
```
