<?php
/**
 * MCP Solr Server - Functions Library
 * 
 * This file contains all the functions for interacting with Apache Solr
 * to manage Job and Company data from peviitor_core.
 * 
 * No OOP - pure procedural PHP code
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

/**
 * Solr connection configuration
 * Reads from environment variables with fallback defaults
 */

// Solr server hostname (default: localhost)
define('SOLR_HOST', getenv('SOLR_HOST') ?: 'localhost');

// Solr server port (default: 8983 - standard Solr port)
define('SOLR_PORT', getenv('SOLR_PORT') ?: '8983');

// Solr username for authentication (default: solr)
define('SOLR_USER', getenv('SOLR_USER') ?: 'solr');

// Solr password for authentication (default: SolrRocks)
define('SOLR_PASS', getenv('SOLR_PASS') ?: 'SolrRocks');

// HTTP or HTTPS protocol (default: http)
define('SOLR_SCHEME', getenv('SOLR_SCHEME') ?: 'http');


// ============================================================================
// CORE HTTP CLIENT
// ============================================================================

/**
 * Makes an HTTP request to Solr server
 * 
 * This is the core function that handles all communication with Apache Solr.
 * It supports both GET and POST methods, handles authentication, and 
 * returns parsed JSON responses.
 * 
 * @param string $core     The Solr core name ('job' or 'company')
 * @param string $method   HTTP method ('GET' or 'POST')
 * @param string $endpoint The API endpoint path (e.g., '/select', '/update')
 * @param array|null $data Optional data to send in POST requests
 * @return array           Response data or error array
 */
function solr_request($core, $method, $endpoint, $data = null)
{
    // Build the full URL: scheme://host:port/solr/core/endpoint
    $url = SOLR_SCHEME . '://' . SOLR_HOST . ':' . SOLR_PORT . '/solr/' . $core . $endpoint;
    
    // Initialize cURL handle for HTTP requests
    $ch = curl_init();
    
    // Set the target URL
    curl_setopt($ch, CURLOPT_URL, $url);
    
    // Set Basic Authentication using username and password
    curl_setopt($ch, CURLOPT_USERPWD, SOLR_USER . ':' . SOLR_PASS);
    
    // Return the response as a string instead of outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set request timeout to 30 seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Set Content-Type header for JSON data
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // Configure request method (GET or POST)
    switch ($method) {
        case 'POST':
            // Enable POST request
            curl_setopt($ch, CURLOPT_POST, true);
            
            // If data provided, encode it as JSON and send in request body
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'GET':
            // Explicitly set GET method
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
    }
    
    // Execute the request and get the response
    $response = curl_exec($ch);
    
    // Get HTTP status code from response
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Get any cURL error that occurred
    $error = curl_error($ch);
    
    // Release the cURL handle (PHP 8.1+ compatible)
    unset($ch);
    
    // If there was a cURL error, return error response
    if ($error) {
        return ['error' => $error];
    }
    
    // If HTTP status code indicates error (4xx or 5xx), return error response
    if ($httpCode >= 400) {
        return ['error' => 'HTTP ' . $httpCode, 'response' => $response];
    }
    
    // Parse JSON response and return as associative array
    return json_decode($response, true);
}


/**
 * Escape a value for use in Solr queries
 * 
 * This function properly escapes special characters in Solr query values.
 * It handles whitespace, special characters, and wraps the value in quotes.
 * 
 * @param string $value The value to escape
 * @return string      The escaped value suitable for Solr queries
 */
function solr_escape($value)
{
    // If value is empty, return wildcard
    if (empty($value)) {
        return '*:*';
    }
    
    // Escape special characters: + - && || ! ( ) { } [ ] ^ " ~ * ? : \
    // Also escape forward slash and backslash
    $escaped = addslashes($value);
    
    // Wrap in quotes for phrase matching
    return '"' . $escaped . '"';
}


// ============================================================================
// JOB FUNCTIONS
// ============================================================================

/**
 * Search for jobs in Solr
 * 
 * Performs a search query against the 'job' core with optional filters.
 * Supports filtering by status, company, workmode, and location.
 * 
 * @param string $query   The Solr query string (default: '*:*' - match all)
 * @param int    $start   Offset for pagination (default: 0)
 * @param int    $rows    Number of results to return (default: 10)
 * @param array  $filters Optional filters: fields, status, company, workmode, location
 * @return array          Solr response with matching documents
 */
function job_select($query = '*:*', $start = 0, $rows = 10, $filters = [])
{
    // Build base query parameters
    $params = [
        'q'     => $query,      // Main query string
        'start' => $start,      // Pagination offset
        'rows'  => $rows,       // Number of results
    ];
    
    // If specific fields requested, add them as comma-separated list
    if (!empty($filters['fields'])) {
        $params['fl'] = implode(',', $filters['fields']);
    }
    
    // Add filter query for status (e.g., 'published', 'verified')
    if (!empty($filters['status'])) {
        $params['fq'][] = 'status:' . $filters['status'];
    }
    
    // Add filter query for company name
    if (!empty($filters['company'])) {
        $params['fq'][] = 'company:' . $filters['company'];
    }
    
    // Add filter query for work mode (remote, on-site, hybrid)
    if (!empty($filters['workmode'])) {
        $params['fq'][] = 'workmode:' . $filters['workmode'];
    }
    
    // Add filter query for location
    if (!empty($filters['location'])) {
        $params['fq'][] = 'location:' . $filters['location'];
    }
    
    // Build query string from parameters
    $queryString = '?' . http_build_query($params);
    
    // Execute GET request to /solr/job/select endpoint
    return solr_request('job', 'GET', '/select' . $queryString);
}


/**
 * Insert a new job into Solr
 * 
 * Adds a new job document to the 'job' core. Uses the 'url' field as
 * the unique key for deduplication (overwrites existing document with same URL).
 * 
 * @param array $data Job data array with fields: url, title, company, cif, 
 *                    location, tags, workmode, date, status, vdate, expirationdate, salary
 * @return array      Solr response from the insert operation
 */
function job_insert($data)
{
    // Build the job document with all fields
    // Uses null coalescing operator to provide defaults
    $doc = [
        'url'            => $data['url'] ?? '',           // Unique key (required)
        'title'         => $data['title'] ?? null,      // Job title
        'company'       => $data['company'] ?? null,    // Company name
        'cif'           => $data['cif'] ?? null,         // Company CIF/CUI
        'location'      => $data['location'] ?? [],     // Array of locations
        'tags'          => $data['tags'] ?? [],          // Array of skill tags
        'workmode'      => $data['workmode'] ?? null,    // remote, on-site, hybrid
        'date'          => $data['date'] ?? date('c'),   // Scrape date (ISO8601)
        'status'        => $data['status'] ?? 'scraped', // Job status
        'vdate'         => $data['vdate'] ?? null,       // Verified date
        'expirationdate'=> $data['expirationdate'] ?? null, // Expiration date
        'salary'        => $data['salary'] ?? null,      // Salary range
    ];
    
    // Build the Solr update payload
    // 'add' operation with doc, boost, overwrite=true for deduplication
    // commitWithin=1000ms for auto-commit after 1 second
    $payload = [
        'add' => ['doc' => $doc, 'boost' => 1, 'overwrite' => true, 'commitWithin' => 1000],
    ];
    
    // Execute POST request to /solr/job/update endpoint
    return solr_request('job', 'POST', '/update', $payload);
}


/**
 * Alias for job_insert
 * 
 * Provides an alternative name for inserting jobs, following the
 * convention where 'index' and 'insert' both mean adding a document.
 * 
 * @param array $data Job data (same as job_insert)
 * @return array      Solr response from the insert operation
 */
function job_index($data)
{
    return job_insert($data);
}


/**
 * Delete a job from Solr by URL
 * 
 * Removes a job document from the 'job' core using its URL as the unique key.
 * 
 * @param string $url The URL of the job to delete (unique key)
 * @return array      Solr response from the delete operation
 */
function job_delete($url)
{
    // Build the delete payload using URL as the unique identifier
    // Uses solr_escape to properly escape the value for Solr queries
    $payload = [
        'delete' => ['query' => 'url:' . solr_escape($url)],
        'commit' => true,  // Commit immediately after delete
    ];
    
    // Execute POST request to /solr/job/update endpoint
    return solr_request('job', 'POST', '/update', $payload);
}


/**
 * Update specific fields of an existing job
 * 
 * Partially updates a job document. The URL must exist in Solr.
 * Only the specified fields will be updated; others remain unchanged.
 * 
 * @param string $url   The URL of the job to update (unique key)
 * @param array  $fields Associative array of field names and new values
 * @return array        Solr response from the update operation
 */
function job_update($url, $fields)
{
    // Start document with the unique key (URL)
    $doc = ['url' => $url];
    
    // Add each field to update to the document
    foreach ($fields as $key => $value) {
        $doc[$key] = $value;
    }
    
    // Build the update payload with commit to ensure changes are visible
    $payload = [
        'add' => ['doc' => $doc, 'overwrite' => true],
        'commit' => true,
    ];
    
    // Execute POST request to /solr/job/update endpoint
    return solr_request('job', 'POST', '/update', $payload);
}


/**
 * Get a single job by URL
 * 
 * Retrieves a specific job document from Solr using its URL as the unique key.
 * 
 * @param string $url The URL of the job to retrieve
 * @return array|null The job document array, or null if not found
 */
function job_get($url)
{
    // Query for the job by URL, limit to 1 result
    $result = job_select('url:' . solr_escape($url), 0, 1);
    
    // If no documents found, return null
    if (empty($result['response']['docs'])) {
        return null;
    }
    
    // Return the first (and only) matching document
    return $result['response']['docs'][0];
}


// ============================================================================
// COMPANY FUNCTIONS
// ============================================================================

/**
 * Search for companies in Solr
 * 
 * Performs a search query against the 'company' core with optional filters.
 * Supports filtering by status and location.
 * 
 * @param string $query   The Solr query string (default: '*:*' - match all)
 * @param int    $start   Offset for pagination (default: 0)
 * @param int    $rows    Number of results to return (default: 10)
 * @param array  $filters Optional filters: fields, status, location
 * @return array          Solr response with matching documents
 */
function company_select($query = '*:*', $start = 0, $rows = 10, $filters = [])
{
    // Build base query parameters
    $params = [
        'q'     => $query,
        'start' => $start,
        'rows'  => $rows,
    ];
    
    // Add specific fields if requested
    if (!empty($filters['fields'])) {
        $params['fl'] = implode(',', $filters['fields']);
    }
    
    // Add filter query for status (activ, suspendat, inactiv, radiat)
    if (!empty($filters['status'])) {
        $params['fq'][] = 'status:' . $filters['status'];
    }
    
    // Add filter query for location
    if (!empty($filters['location'])) {
        $params['fq'][] = 'location:' . $filters['location'];
    }
    
    // Build query string
    $queryString = '?' . http_build_query($params);
    
    // Execute GET request to /solr/company/select endpoint
    return solr_request('company', 'GET', '/select' . $queryString);
}


/**
 * Insert a new company into Solr
 * 
 * Adds a new company document to the 'company' core. Uses the 'id' field
 * (CIF/CUI) as the unique key for deduplication.
 * 
 * @param array $data Company data array with fields: id, company, status, 
 *                    location, website, career
 * @return array      Solr response from the insert operation
 */
function company_insert($data)
{
    // Build the company document with all fields
    $doc = [
        'id'       => $data['id'] ?? '',         // Unique CIF/CUI (required)
        'company'  => $data['company'] ?? null,   // Company legal name
        'status'   => $data['status'] ?? null,   // Company status
        'location' => $data['location'] ?? [],   // Array of locations
        'website'  => $data['website'] ?? [],    // Array of website URLs
        'career'   => $data['career'] ?? [],     // Array of career page URLs
    ];
    
    // Build the Solr update payload
    $payload = [
        'add' => ['doc' => $doc, 'boost' => 1, 'overwrite' => true, 'commitWithin' => 1000],
    ];
    
    // Execute POST request to /solr/company/update endpoint
    return solr_request('company', 'POST', '/update', $payload);
}


/**
 * Alias for company_insert
 * 
 * Provides an alternative name for inserting companies.
 * 
 * @param array $data Company data (same as company_insert)
 * @return array      Solr response from the insert operation
 */
function company_index($data)
{
    return company_insert($data);
}


/**
 * Delete a company from Solr by ID (CIF)
 * 
 * Removes a company document from the 'company' core using its CIF as the unique key.
 * 
 * @param string $id The CIF/CUI of the company to delete
 * @return array    Solr response from the delete operation
 */
function company_delete($id)
{
    // Build the delete payload using ID (CIF) as the unique identifier
    $payload = [
        'delete' => ['query' => 'id:' . solr_escape($id)],
        'commit' => true,
    ];
    
    // Execute POST request to /solr/company/update endpoint
    return solr_request('company', 'POST', '/update', $payload);
}


/**
 * Update specific fields of an existing company
 * 
 * Partially updates a company document. The ID (CIF) must exist in Solr.
 * 
 * @param string $id     The CIF/CUI of the company to update (unique key)
 * @param array  $fields Associative array of field names and new values
 * @return array         Solr response from the update operation
 */
function company_update($id, $fields)
{
    // Start document with the unique key (ID/CIF)
    $doc = ['id' => $id];
    
    // Add each field to update to the document
    foreach ($fields as $key => $value) {
        $doc[$key] = $value;
    }
    
    // Build the update payload
    $payload = [
        'add' => ['doc' => $doc, 'overwrite' => true],
        'commit' => true,
    ];
    
    // Execute POST request to /solr/company/update endpoint
    return solr_request('company', 'POST', '/update', $payload);
}


/**
 * Get a single company by ID (CIF)
 * 
 * Retrieves a specific company document from Solr using its CIF as the unique key.
 * 
 * @param string $id The CIF/CUI of the company to retrieve
 * @return array|null The company document array, or null if not found
 */
function company_get($id)
{
    // Query for the company by ID, limit to 1 result
    $result = company_select('id:' . solr_escape($id), 0, 1);
    
    // If no documents found, return null
    if (empty($result['response']['docs'])) {
        return null;
    }
    
    // Return the first (and only) matching document
    return $result['response']['docs'][0];
}
