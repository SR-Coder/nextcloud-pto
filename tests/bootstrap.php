<?php

declare(strict_types=1);

// PHPUnit bootstrap file

if (!defined('PHPUNIT_RUN')) {
    define('PHPUNIT_RUN', 1);
}

require_once __DIR__ . '/../vendor/autoload.php';

// Mock Nextcloud environment for testing
// In real integration tests, you'd set up a test Nextcloud instance
