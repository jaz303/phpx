<?php
// Adjust this to point to the ztest library
// (relative to the current working directory)
require 'ztest/ztest.php';

require 'lib/phpx.php';
phpx\Stream::load_phpx();

// Create test suite
$suite = new ztest\TestSuite("phpx test suite");

// Recursively scan the 'test' directory and require() all PHP source files
// Again, 'test' is relative to the current working directory.
$suite->require_all('test');

// Add non-abstract subclasses of ztest\TestCase as test-cases to be run
$suite->auto_fill();

// And away we go.
$suite->run(new ztest\ConsoleReporter);
?>