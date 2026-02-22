#!/usr/bin/env php
<?php
/**
 * Test script for MCP Solr Company operations
 * 
 * Usage: php test/company_test.php
 */

require_once __DIR__ . '/../src/functions.php';

$testResults = [
    'passed' => 0,
    'failed' => 0,
    'tests' => [],
];

function runTest($name, $callback) {
    global $testResults;
    
    try {
        $result = $callback();
        if ($result === true || (isset($result['success']) && $result['success'])) {
            $testResults['passed']++;
            $testResults['tests'][] = ['name' => $name, 'status' => 'PASSED'];
            echo "✓ $name\n";
            return true;
        } else {
            $testResults['failed']++;
            $testResults['tests'][] = ['name' => $name, 'status' => 'FAILED', 'reason' => $result['reason'] ?? 'Unknown error'];
            echo "✗ $name: " . ($result['reason'] ?? 'Unknown error') . "\n";
            return false;
        }
    } catch (Exception $e) {
        $testResults['failed']++;
        $testResults['tests'][] = ['name' => $name, 'status' => 'FAILED', 'reason' => $e->getMessage()];
        echo "✗ $name: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "\n=== MCP Solr Company Tests ===\n\n";

$testCompany = [
    'id' => 'TEST' . time(),
    'name' => 'Test Company SRL',
    'cif' => 'TEST' . time(),
    'address' => 'Bucuresti, Romania',
    'website' => 'https://example.com',
    'description' => 'Test company description',
    'status' => 'active',
];

runTest('company_insert - Insert a new company', function() use ($testCompany) {
    $result = company_insert($testCompany);
    if (isset($result['responseHeader']['status']) && $result['responseHeader']['status'] === 0) {
        return true;
    }
    return ['reason' => json_encode($result)];
});

runTest('company_get - Get the inserted company', function() use ($testCompany) {
    $result = company_get($testCompany['id']);
    if (isset($result['id']) && $result['id'] === $testCompany['id']) {
        return true;
    }
    return ['reason' => 'Company not found or ID mismatch'];
});

runTest('company_select - Select all companies', function() {
    $result = company_select('*:*', 0, 10);
    if (isset($result['response']['numFound'])) {
        return true;
    }
    return ['reason' => 'Invalid response'];
});

runTest('company_update - Update company fields', function() use ($testCompany) {
    $result = company_update($testCompany['id'], ['name' => 'Updated Test Company']);
    if (isset($result['responseHeader']['status']) && $result['responseHeader']['status'] === 0) {
        $updated = company_get($testCompany['id']);
        if ($updated['name'] === 'Updated Test Company') {
            return true;
        }
        return ['reason' => 'Name not updated'];
    }
    return ['reason' => json_encode($result)];
});

runTest('company_delete - Delete the test company', function() use ($testCompany) {
    $result = company_delete($testCompany['id']);
    if (isset($result['responseHeader']['status']) && $result['responseHeader']['status'] === 0) {
        $deleted = company_get($testCompany['id']);
        if ($deleted === null) {
            return true;
        }
        return ['reason' => 'Company still exists after delete'];
    }
    return ['reason' => json_encode($result)];
});

echo "\n=== Results ===\n";
echo "Passed: {$testResults['passed']}\n";
echo "Failed: {$testResults['failed']}\n";
echo "Total: " . ($testResults['passed'] + $testResults['failed']) . "\n";

exit($testResults['failed'] > 0 ? 1 : 0);
