---
description: Generate complete project documentation (code review, tests, how-to)
agent: explore
model: opencode/big-pickle
---

Generate comprehensive documentation for the MCP Solr project:

1. **Code Review**: Review all PHP source files:
   - src/functions.php - All Solr functions
   - mcp-server.php - MCP server implementation
   - test/job_test.php - Job core tests
   - test/company_test.php - Company core tests

2. **Test Coverage**: Document all test cases and their status

3. **How to Use**: Create detailed usage instructions including:
   - Prerequisites (Docker, Solr)
   - Configuration via environment variables
   - Docker commands for building and running
   - Testing the MCP server
   - Example JSON-RPC requests

4. **Generate HTML Documentation**: Create docs/index.html with:
   - Project overview
   - Code review section
   - Test results
   - How-to guides
   - API reference

Output the full documentation and save it to docs/index.html
