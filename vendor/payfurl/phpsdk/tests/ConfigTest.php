<?php

declare(strict_types=1);

use payFURL\Sdk\Config;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . "/../src/Config.php");
require_once(__DIR__ . "/../src/tools/Region.php");
require_once(__DIR__ . "/TestBase.php");
require_once(__DIR__ . "/../src/ResponseException.php");

final class ConfigTest extends TestBase
{
    private static string $SECRET_KEY = 'your_secret_key';

    private static function getSecretKeyWithRegion($region): string
    {
        return sprintf('%s-%s', self::$SECRET_KEY, $region);
    }

    public function testGetBaseUriForValidRegionAndEnvironment()
    {
        Config::initialise(self::getSecretKeyWithRegion('au'), 'production');
        $this->assertEquals('https://api-au.payfurl.com', Config::$BaseUrl);
    }

    public function testGetBaseUriForInvalidRegionAndValidEnvironment()
    {
        Config::initialise(self::getSecretKeyWithRegion('xyz'), 'production');
        $this->assertEquals('https://api.payfurl.com', Config::$BaseUrl);
    }

    public function testGetBaseUriForValidRegionAndInvalidEnvironment()
    {
        Config::initialise(self::getSecretKeyWithRegion('au'), 'INVALID_ENVIRONMENT');
        $this->assertEquals('https://sandbox-api.payfurl.com', Config::$BaseUrl);
    }

    public function testGetBaseUriForInvalidRegionAndEnvironment()
    {
        Config::initialise(self::getSecretKeyWithRegion('xyz'), 'INVALID_ENVIRONMENT');
        $this->assertEquals('https://sandbox-api.payfurl.com', Config::$BaseUrl);
    }

    public function testGetBaseUriForLocalEnvironment()
    {
        Config::initialise(self::getSecretKeyWithRegion(''), 'local');
        $this->assertEquals('https://localhost:5001', Config::$BaseUrl);
    }

    public function testGetBaseUriForSandboxEnvironment()
    {
        Config::initialise(self::getSecretKeyWithRegion(''), 'sandbox');
        $this->assertEquals('https://sandbox-api.payfurl.com', Config::$BaseUrl);
    }

    public function testGetBaseUriForProdEnvironment()
    {
        Config::initialise(self::getSecretKeyWithRegion(''), 'production');
        $this->assertEquals('https://api.payfurl.com', Config::$BaseUrl);
    }
}
