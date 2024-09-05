<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../src/tools/ArrayTools.php');
require_once(__DIR__ . '/../src/ResponseException.php');

use payFURL\Sdk\ArrayTools;
use payFURL\Sdk\ResponseException;

final class ArrayToolsTest extends TestCase
{
    public function testSimpleValidKeys(): void
    {
        $this->assertNull(ArrayTools::ValidateKeys(['Item1' => 123, 'Item2' => 'ahah'], ['Item1', 'Item2']));
    }

    public function testNestedValidKeys(): void
    {
        $this->assertNull(ArrayTools::ValidateKeys(['Item1' => 123, 'Item2' => 'ahah', 'Item3' => ['Item3.1' => 'aaa', 'Item3.2' => 'bbb']], ['Item1', 'Item2', 'Item3' => ['Item3.1', 'Item3.2']]));
    }

    public function testSimpleInvalidKeysNullKeys(): void
    {
        $this->expectException(ResponseException::class);
        ArrayTools::ValidateKeys(['Item1' => null, 'Item2' => 'ahah'], ['Item1', 'Item2']);
    }

    public function testSimpleInvalidKeysMissingKeys(): void
    {
        $this->expectException(ResponseException::class);
        ArrayTools::ValidateKeys(['Item1' => ''], ['Item1', 'Item2']);
    }

    public function testNestedInvalidKeys(): void
    {
        $this->expectException(ResponseException::class);
        ArrayTools::ValidateKeys(['Item1' => 123, 'Item2' => 'ahah', 'Item3' => ['Item3.1' => 'aaa']], ['Item1', 'Item2', 'Item3' => ['Item3.1', 'Item3.2']]);
    }
}
