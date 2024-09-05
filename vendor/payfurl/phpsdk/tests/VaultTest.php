<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../src/Config.php');
require_once(__DIR__ . '/../src/Vault.php');
require_once(__DIR__ . '/TestBase.php');
require_once(__DIR__ . '/../src/ResponseException.php');

use payFURL\Sdk\Vault;
use payFURL\Sdk\ResponseException;

final class VaultTest extends TestBase
{
    /**
     * @throws ResponseException
     */
    public function testCreate(): void
    {
        $svc = new Vault();

        $result = $svc->Create(['CardNumber' => '4111111111111111']);

        $this->assertIsString($result['vaultId']);
    }

    /**
     * @throws ResponseException
     */
    public function testSingle(): void
    {
        $svc = new Vault();

        $vault = $svc->Create(['CardNumber' => '4111111111111111']);

        $result = $svc->Single($vault['vaultId']);

        $this->assertSame($result['vaultId'], $vault['vaultId']);
    }

    /**
     * @throws ResponseException
     */
    public function testDelete(): void
    {
        $svc = new Vault();

        $vault = $svc->Create(['CardNumber' => '4111111111111111']);

        $result = $svc->Delete($vault['vaultId']);

        $this->assertSame($result['vaultId'], $vault['vaultId']);
    }
}
