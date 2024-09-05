<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/TestConfiguration.php');
require_once(__DIR__ . '/../src/Config.php');

use payFURL\Sdk\Config;

class TestBase extends TestCase
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        TestConfiguration::setUp();
        Config::initialise(TestConfiguration::getSecretKey(), TestConfiguration::getEnvironment());
    }
}
