<?php

declare(strict_types=1);

namespace Lukman\Config\Tests;

use Lukman\Config\EnvLoader;
use Lukman\Config\Exception\ConfigNotFoundException;
use PHPUnit\Framework\TestCase;

class EnvLoaderTest extends TestCase
{
    private string $envPath;

    /**
     * @var array<string, mixed>
     */
    private array $originalEnv = [];

    /**
     * @var array<string, mixed>
     */
    private array $originalServer = [];

    protected function setUp(): void
    {
        $this->envPath = __DIR__ . DIRECTORY_SEPARATOR . '.env.test';
        $this->originalEnv = $_ENV;
        $this->originalServer = $_SERVER;

        foreach (['APP_NAME', 'EMPTY', 'DEBUG', 'CACHE', 'NULL_VALUE', 'PORT', 'RATIO', 'EXISTING'] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->envPath)) {
            unlink($this->envPath);
        }

        foreach (['APP_NAME', 'EMPTY', 'DEBUG', 'CACHE', 'NULL_VALUE', 'PORT', 'RATIO', 'EXISTING'] as $key) {
            putenv($key);
        }

        $_ENV = $this->originalEnv;
        $_SERVER = $this->originalServer;
    }

    public function testMissingFileThrowsConfigNotFoundException(): void
    {
        $this->expectException(ConfigNotFoundException::class);

        (new EnvLoader())->load($this->envPath);
    }

    public function testParseIgnoresCommentsAndEmptyLines(): void
    {
        $values = (new EnvLoader())->parse("
            # Comment

            APP_NAME=Demo
        ");

        $this->assertSame(['APP_NAME' => 'Demo'], $values);
    }

    public function testParseQuotedAndEmptyValues(): void
    {
        $values = (new EnvLoader())->parse(implode(PHP_EOL, [
            'APP_NAME="Demo App"',
            "CACHE='file cache'",
            'EMPTY=',
        ]));

        $this->assertSame('Demo App', $values['APP_NAME']);
        $this->assertSame('file cache', $values['CACHE']);
        $this->assertSame('', $values['EMPTY']);
    }

    public function testParseScalarValues(): void
    {
        $values = (new EnvLoader())->parse(implode(PHP_EOL, [
            'DEBUG=true',
            'CACHE=false',
            'NULL_VALUE=null',
            'PORT=8080',
            'RATIO=1.5',
        ]));

        $this->assertTrue($values['DEBUG']);
        $this->assertFalse($values['CACHE']);
        $this->assertNull($values['NULL_VALUE']);
        $this->assertSame(8080, $values['PORT']);
        $this->assertSame(1.5, $values['RATIO']);
    }

    public function testLoadFillsEnvAndServer(): void
    {
        file_put_contents($this->envPath, 'APP_NAME=Demo');

        (new EnvLoader())->load($this->envPath);

        $this->assertSame('Demo', $_ENV['APP_NAME']);
        $this->assertSame('Demo', $_SERVER['APP_NAME']);
    }

    public function testLoadDoesNotOverwriteExistingValueByDefault(): void
    {
        $_ENV['EXISTING'] = 'old';
        $_SERVER['EXISTING'] = 'old';
        file_put_contents($this->envPath, 'EXISTING=new');

        (new EnvLoader())->load($this->envPath);

        $this->assertSame('old', $_ENV['EXISTING']);
        $this->assertSame('old', $_SERVER['EXISTING']);
    }

    public function testLoadOverwritesExistingValueWhenEnabled(): void
    {
        $_ENV['EXISTING'] = 'old';
        $_SERVER['EXISTING'] = 'old';
        file_put_contents($this->envPath, 'EXISTING=new');

        (new EnvLoader())->load($this->envPath, true);

        $this->assertSame('new', $_ENV['EXISTING']);
        $this->assertSame('new', $_SERVER['EXISTING']);
    }
}
