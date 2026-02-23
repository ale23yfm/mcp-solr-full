#!/usr/bin/env php
<?php
/**
 * MCP Server for Apache Solr
 * 
 * This is the main entry point for the MCP (Model Context Protocol) server.
 * It provides a JSON-RPC 2.0 interface over STDIO to interact with
 * Apache Solr for managing Job and Company data.
 * 
 * Usage:
 *   php mcp-server.php
 * 
 * The server reads JSON-RPC requests from STDIN and writes responses to STDOUT.
 * Each request must be a JSON object with 'method' and 'params' fields.
 * 
 * Configuration:
 *   Set SOLR_HOST, SOLR_PORT, SOLR_USER, SOLR_PASS, SOLR_SCHEME
 *   in config.php or as environment variables.
 */

// Include the functions library that contains all Solr operations
require_once __DIR__ . '/src/functions.php';


// ============================================================================
// MCP PROTOCOL HELPERS
// ============================================================================

/**
 * Read a JSON object from STDIN
 * 
 * Reads a line from standard input and parses it as JSON.
 * Used to receive JSON-RPC requests from the MCP client.
 * 
 * @return array|null Parsed JSON array, or null if invalid
 */
function mcp_read_json()
{
    // Read one line from standard input
    $input = fgets(STDIN);
    
    // Parse JSON into associative array
    $data = json_decode($input, true);
    
    // Check if JSON was valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $data;
}


/**
 * Send a JSON response to STDOUT
 * 
 * Outputs a JSON-encoded response and flushes the output buffer
 * to ensure immediate delivery to the MCP client.
 * 
 * @param array $result The result data to send
 */
function mcp_response($result)
{
    $json = json_encode($result);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $json = json_encode(['error' => 'JSON encoding error: ' . json_last_error_msg()]);
    }
    
    echo $json . "\n";
    fflush(STDOUT);
}


/**
 * Send an error response
 * 
 * Helper function to send an error response with a message.
 * 
 * @param string $message The error message to send
 */
function mcp_error($message)
{
    mcp_response(['error' => $message]);
}


// ============================================================================
// METHOD REGISTRY
// ============================================================================

/**
 * Available MCP methods
 * 
 * This array maps method names to handler functions.
 * Each handler validates required parameters and calls the appropriate
 * function from functions.php.
 */
$methods = [
    // MCP Protocol Methods
    'initialize' => function($params) {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => (object)[],
            ],
            'serverInfo' => [
                'name' => 'mcp-solr',
                'version' => '1.0.0',
            ],
        ];
    },
    
    'tools/list' => function($params) {
        $tools = [];
        
        $toolDefinitions = [
            'job_select' => 'Search jobs with flexible queries',
            'job_search' => 'Search jobs with common filters',
            'job_insert' => 'Insert a new job (url is required)',
            'job_index' => 'Alias for job_insert',
            'job_delete' => 'Delete a job by url',
            'job_update' => 'Update job fields by url',
            'job_get' => 'Get a job by url',
            'company_select' => 'Search companies',
            'company_search' => 'Search companies with common filters',
            'company_insert' => 'Insert a new company (id is required)',
            'company_index' => 'Alias for company_insert',
            'company_delete' => 'Delete a company by id',
            'company_update' => 'Update company fields by id',
            'company_get' => 'Get a company by id',
        ];
        
        $insertSchema = [
            'url' => ['type' => 'string', 'description' => 'Job URL (unique key)'],
            'title' => ['type' => 'string', 'description' => 'Job title'],
            'company' => ['type' => 'string', 'description' => 'Company name'],
            'location' => ['type' => 'array', 'description' => 'Job locations'],
            'tags' => ['type' => 'array', 'description' => 'Skill tags'],
            'workmode' => ['type' => 'string', 'description' => 'Work mode (remote, on-site, hybrid)'],
            'salary' => ['type' => 'string', 'description' => 'Salary range'],
            'description' => ['type' => 'string', 'description' => 'Job description'],
        ];
        
        $deleteSchema = [
            'url' => ['type' => 'string', 'description' => 'Job URL'],
        ];
        
        $idSchema = [
            'id' => ['type' => 'string', 'description' => 'Company ID/CIF'],
        ];
        
        $updateSchema = [
            'url' => ['type' => 'string', 'description' => 'Job URL'],
            'id' => ['type' => 'string', 'description' => 'Company ID'],
            'fields' => ['type' => 'object', 'description' => 'Fields to update'],
        ];
        
        $defaultSchema = [
            'query' => ['type' => 'string', 'description' => 'Search query'],
            'term' => ['type' => 'string', 'description' => 'Search term'],
            'start' => ['type' => 'integer', 'description' => 'Start index'],
            'rows' => ['type' => 'integer', 'description' => 'Number of results'],
            'offset' => ['type' => 'integer', 'description' => 'Offset'],
            'limit' => ['type' => 'integer', 'description' => 'Limit results'],
            'filters' => ['type' => 'object', 'description' => 'Filter criteria'],
            'status' => ['type' => 'string', 'description' => 'Job/Company status'],
            'company' => ['type' => 'string', 'description' => 'Company name'],
            'workmode' => ['type' => 'string', 'description' => 'Work mode'],
            'location' => ['type' => 'string', 'description' => 'Location'],
        ];
        
        $insertMethods = ['job_insert', 'job_index', 'company_insert', 'company_index'];
        $deleteMethods = ['job_delete', 'job_get'];
        $idMethods = ['company_delete', 'company_get'];
        $updateMethods = ['job_update', 'company_update'];
        
        foreach ($toolDefinitions as $name => $description) {
            if (in_array($name, $insertMethods)) {
                $schema = $insertSchema;
            } elseif (in_array($name, $deleteMethods)) {
                $schema = $deleteSchema;
            } elseif (in_array($name, $idMethods)) {
                $schema = $idSchema;
            } elseif (in_array($name, $updateMethods)) {
                $schema = $updateSchema;
            } else {
                $schema = $defaultSchema;
            }
            
            $tools[] = [
                'name' => $name,
                'description' => $description,
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $schema,
                ],
            ];
        }
        
        return ['tools' => $tools];
    },
    
    'ping' => function($params) {
        return [];
    },
    
    'tools/call' => function($params) use (&$methods) {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];
        
        if (!$name) {
            return ['error' => 'Tool name is required'];
        }
        
        if (!isset($methods[$name])) {
            return ['error' => 'Tool not found: ' . $name];
        }
        
        return $methods[$name]($arguments);
    },
    
    /**
     * Search for jobs in Solr
     * 
     * @param array $params Keys: query, start, rows, filters
     * @return array Solr response with matching jobs
     */
    'job_select' => function($params) {
        $query = $params['query'] ?? '*:*';
        $start = $params['start'] ?? 0;
        $rows = $params['rows'] ?? 10;
        $filters = $params['filters'] ?? [];
        
        return job_select($query, $start, $rows, $filters);
    },
    
    /**
     * Search jobs with common filters
     * 
     * @param array $params Keys: term, status, company, workmode, location, offset, limit
     * @return array Solr response with matching jobs
     */
    'job_search' => function($params) {
        $term = $params['term'] ?? '*:*';
        $filters = [
            'status' => $params['status'] ?? null,
            'company' => $params['company'] ?? null,
            'workmode' => $params['workmode'] ?? null,
            'location' => $params['location'] ?? null,
        ];
        // Remove null values from filters
        $filters = array_filter($filters);
        
        return job_select($term, $params['offset'] ?? 0, $params['limit'] ?? 20, $filters);
    },
    
    /**
     * Insert a new job
     * 
     * @param array $params Job data (url is required)
     * @return array Solr response or error
     */
    'job_insert' => function($params) {
        // Validate required parameter
        if (empty($params['url'])) {
            return ['error' => 'url is required'];
        }
        return job_insert($params);
    },
    
    /**
     * Alias for job_insert
     * 
     * @param array $params Job data
     * @return array Solr response
     */
    'job_index' => function($params) {
        return job_insert($params);
    },
    
    /**
     * Delete a job by URL
     * 
     * @param array $params Must contain 'url'
     * @return array Solr response or error
     */
    'job_delete' => function($params) {
        if (empty($params['url'])) {
            return ['error' => 'url is required'];
        }
        return job_delete($params['url']);
    },
    
    /**
     * Update job fields
     * 
     * @param array $params Must contain 'url' and 'fields'
     * @return array Solr response or error
     */
    'job_update' => function($params) {
        if (empty($params['url']) || empty($params['fields'])) {
            return ['error' => 'url and fields are required'];
        }
        return job_update($params['url'], $params['fields']);
    },
    
    /**
     * Get a job by URL
     * 
     * @param array $params Must contain 'url'
     * @return array|null Job document or error
     */
    'job_get' => function($params) {
        if (empty($params['url'])) {
            return ['error' => 'url is required'];
        }
        return job_get($params['url']);
    },
    
    /**
     * Search for companies in Solr
     * 
     * @param array $params Keys: query, start, rows, filters
     * @return array Solr response with matching companies
     */
    'company_select' => function($params) {
        $query = $params['query'] ?? '*:*';
        $start = $params['start'] ?? 0;
        $rows = $params['rows'] ?? 10;
        $filters = $params['filters'] ?? [];
        
        return company_select($query, $start, $rows, $filters);
    },
    
    /**
     * Search companies with common filters
     * 
     * @param array $params Keys: term, status, location, offset, limit
     * @return array Solr response with matching companies
     */
    'company_search' => function($params) {
        $term = $params['term'] ?? '*:*';
        $filters = [
            'status' => $params['status'] ?? null,
            'location' => $params['location'] ?? null,
        ];
        $filters = array_filter($filters);
        
        return company_select($term, $params['offset'] ?? 0, $params['limit'] ?? 20, $filters);
    },
    
    /**
     * Insert a new company
     * 
     * @param array $params Company data (id/CIF is required)
     * @return array Solr response or error
     */
    'company_insert' => function($params) {
        if (empty($params['id'])) {
            return ['error' => 'id is required'];
        }
        return company_insert($params);
    },
    
    /**
     * Alias for company_insert
     * 
     * @param array $params Company data
     * @return array Solr response
     */
    'company_index' => function($params) {
        return company_insert($params);
    },
    
    /**
     * Delete a company by ID (CIF)
     * 
     * @param array $params Must contain 'id'
     * @return array Solr response or error
     */
    'company_delete' => function($params) {
        if (empty($params['id'])) {
            return ['error' => 'id is required'];
        }
        return company_delete($params['id']);
    },
    
    /**
     * Update company fields
     * 
     * @param array $params Must contain 'id' and 'fields'
     * @return array Solr response or error
     */
    'company_update' => function($params) {
        if (empty($params['id']) || empty($params['fields'])) {
            return ['error' => 'id and fields are required'];
        }
        return company_update($params['id'], $params['fields']);
    },
    
    /**
     * Get a company by ID (CIF)
     * 
     * @param array $params Must contain 'id'
     * @return array|null Company document or error
     */
    'company_get' => function($params) {
        if (empty($params['id'])) {
            return ['error' => 'id is required'];
        }
        return company_get($params['id']);
    },
];


// ============================================================================
// INITIALIZE SERVER
// ============================================================================

/**
 * Send server capabilities on startup
 * 
 * On initialization, the server announces its capabilities to the client,
 * listing all available methods that can be called.
 */
mcp_response(['jsonrpc' => '2.0', 'id' => null, 'result' => ['capabilities' => ['tools' => array_keys($methods)]]]);


// ============================================================================
// MAIN REQUEST LOOP
// ============================================================================

/**
 * Main request processing loop
 * 
 * Continuously reads JSON-RPC requests from STDIN, processes them,
 * and sends responses to STDOUT until STDIN is closed (EOF).
 */
while (!feof(STDIN)) {
    // Read a line from STDIN
    $line = fgets(STDIN);
    
    // Skip empty lines
    if (empty(trim($line))) {
        continue;
    }
    
    // Parse the JSON-RPC request
    $request = json_decode($line, true);
    
    // Check if 'method' field exists in request
    if (!isset($request['method'])) {
        mcp_error('Method not specified');
        continue;
    }
    
    // Extract method name, parameters, and request ID
    $method = $request['method'];
    $params = $request['params'] ?? [];
    $id = $request['id'] ?? null;
    
    // Check if the requested method exists
    if (!isset($methods[$method])) {
        // Return JSON-RPC error for unknown method
        mcp_response(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => 'Method not found']]);
        continue;
    }
    
    // Execute the method with error handling
    try {
        // Call the method handler with parameters
        $result = $methods[$method]($params);
        
        // Send successful response
        mcp_response(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
    } catch (\Exception $e) {
        // Catch any exceptions and return as JSON-RPC error
        mcp_response(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32603, 'message' => $e->getMessage()]]);
    }
}
