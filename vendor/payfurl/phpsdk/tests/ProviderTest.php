<?php declare(strict_types=1);

use payFURL\Sdk\Provider;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/TestConfiguration.php');
require_once(__DIR__ . '/../src/Config.php');
require_once(__DIR__ . '/../src/Provider.php');
require_once(__DIR__ . '/TestBase.php');
require_once(__DIR__ . '/../src/ResponseException.php');

use payFURL\Sdk\Config;
use payFURL\Sdk\Customer;
use payFURL\Sdk\ResponseException;

final class ProviderTest extends TestBase
{
    /**
     * @throws ResponseException
     */
    public function testCreateProvider(): void
    {
        $svc = new Provider();

        $result = $svc->Create($this->getProvider());

        $this->assertIsString($result['providerId']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testUpdateProvider(): void
    {
        $svc = new Provider();

        $result = $svc->Create($this->getProvider());

        $this->assertIsString($result['providerId']);

        $newName = bin2hex(random_bytes(16));
        $updateProvider = [
            "Name" => $newName
        ];

        $result = $svc->Update($result['providerId'], $updateProvider);

        $this->assertEquals($result['name'], $newName);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testDeleteProvider(): void
    {
        $svc = new Provider();

        $result = $svc->Create($this->getProvider());

        $this->assertIsString($result['providerId']);

        $deletedProvider = $svc->Delete($result['providerId']);

        $this->assertEquals($result['providerId'], $deletedProvider['providerId']);
    }

    /**
     * @throws Exception
     */
    private function getProvider(): array
    {
        return [
            "Type" => "dummy",
            "Name" => bin2hex(random_bytes(16)),
            "Environment" => "SANDBOX",
            "Currency" => "AUD",
            "AuthenticationParameters" => [
                "MinMilliseconds" => "1",
                "MaxMilliseconds" => "10"
            ]];
    }
}
