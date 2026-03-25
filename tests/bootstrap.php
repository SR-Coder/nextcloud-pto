<?php

/**
 * PHPUnit bootstrap for PTO Tracker tests
 */

if (!defined('PHPUNIT_RUN')) {
    define('PHPUNIT_RUN', 1);
}

require_once __DIR__ . '/../../../lib/base.php';

// Additional test setup can go here
\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');
\OC_App::loadApp('pto');
