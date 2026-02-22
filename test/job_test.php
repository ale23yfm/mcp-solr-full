#!/usr/bin/env php
<?php
/**
 * Test script for MCP Solr Job operations
 * 
 * Usage: php test/job_test.php
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

echo "\n=== MCP Solr Job Tests ===\n\n";

$testJob = [
    'url' => 'https://example.com/job/test-' . time(),
    'title' => 'PHP Developer Test',
    'company' => 'Test Company',
    'location' => ['Bucuresti'],
    'tags' => ['PHP', 'MySQL'],
    'workmode' => 'hybrid',
    'salary' => '5000-8000',
    'description' => 'Test job description',
    'status' => 'scraped',
];

runTest('job_insert - Insert a new job', function() use ($testJob) {
    $result = job_insert($testJob);
    if (isset($result['responseHeader']['status']) && $result['responseHeader']['status'] === 0) {
        return true;
    }
    return ['reason' => json_encode($result)];
});

runTest('job_get - Get the inserted job', function() use ($testJob) {
    $result = job_get($testJob['url']);
    if (isset($result['url']) && $result['url'] === $testJob['url']) {
        return true;
    }
    return ['reason' => 'Job not found or URL mismatch'];
});

runTest('job_select - Select all jobs', function() {
    $result = job_select('*:*', 0, 10);
    if (isset($result['response']['numFound'])) {
        return true;
    }
    return ['reason' => 'Invalid response'];
});

runTest('job_search - Search jobs by term', function() {
    $result = job_select('PHP', 0, 10);
    if (isset($result['response']['numFound'])) {
        return true;
    }
    return ['reason' => 'Invalid response'];
});

runTest('job_update - Update job fields', function() use ($testJob) {
    $result = job_update($testJob['url'], ['title' => 'Updated PHP Developer']);
    if (isset($result['responseHeader']['status']) && $result['responseHeader']['status'] === 0) {
        $updated = job_get($testJob['url']);
        if ($updated['title'] === 'Updated PHP Developer') {
            return true;
        }
        return ['reason' => 'Title not updated'];
    }
    return ['reason' => json_encode($result)];
});

runTest('job_delete - Delete the test job', function() use ($testJob) {
    $result = job_delete($testJob['url']);
    if (isset($result['responseHeader']['status']) && $result['responseHeader']['status'] === 0) {
        $deleted = job_get($testJob['url']);
        if ($deleted === null) {
            return true;
        }
        return ['reason' => 'Job still exists after delete'];
    }
    return ['reason' => json_encode($result)];
});

echo "\n=== Results ===\n";
echo "Passed: {$testResults['passed']}\n";
echo "Failed: {$testResults['failed']}\n";
echo "Total: " . ($testResults['passed'] + $testResults['failed']) . "\n";

exit($testResults['failed'] > 0 ? 1 : 0);
