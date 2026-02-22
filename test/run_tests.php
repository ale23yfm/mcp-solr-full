#!/usr/bin/env php
<?php
/**
 * Test runner for MCP Solr
 * 
 * Usage: php test/run_tests.php
 * 
 * Requires Solr to be running.
 */

$containerName = 'peviitor-solr';

echo "=== MCP Solr Test Runner ===\n\n";

echo "Checking if Solr is running...\n";
exec("docker ps --filter name=$containerName --format '{{.Status}}'", $output, $exitCode);

if ($exitCode !== 0 || empty($output)) {
    echo "ERROR: Solr container '$containerName' is not running!\n";
    echo "Please start Solr with: docker start $containerName\n";
    exit(1);
}

echo "Solr is running.\n\n";

echo "Running job tests...\n";
echo str_repeat('-', 40) . "\n";
exec('php ' . __DIR__ . '/job_test.php 2>&1', $jobOutput, $jobExitCode);
echo implode("\n", $jobOutput) . "\n\n";

echo "Running company tests...\n";
echo str_repeat('-', 40) . "\n";
exec('php ' . __DIR__ . '/company_test.php 2>&1', $companyOutput, $companyExitCode);
echo implode("\n", $companyOutput) . "\n\n";

echo "=== Summary ===\n";
if ($jobExitCode === 0 && $companyExitCode === 0) {
    echo "All tests passed!\n";
    exit(0);
} else {
    echo "Some tests failed!\n";
    exit(1);
}
