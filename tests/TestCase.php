<?php

namespace SolverCircle\LogViewer\Tests;

use SolverCircle\LogViewer\LogViewerServiceProvider;

if (class_exists(\Orchestra\Testbench\TestCase::class)) {
    abstract class BaseTestCase extends \Orchestra\Testbench\TestCase
    {
        protected function getPackageProviders($app): array
        {
            return [
                LogViewerServiceProvider::class,
            ];
        }
    }
} else {
    abstract class BaseTestCase extends \Tests\TestCase {}
}

abstract class TestCase extends BaseTestCase {}
