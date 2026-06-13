<?php

declare(strict_types=1);

namespace Lukman\Config\Tests;

use Lukman\Config\ConfigLoader;
use Lukman\Config\Exception\ConfigException;
use Lukman\Config\Exception\ConfigNotFoundException;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{
    private string $configPath;

    protected function setUp(): void
    {
        $this->configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config-loader-temp';
        mkdir($this->configPath);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->configPath);
    }

    public function testMissingDirectoryThrowsConfigNotFoundException(): void
    {
        $this->expectException(ConfigNotFoundException::class);

        (new ConfigLoader())->load($this->configPath . DIRECTORY_SEPARATOR . 'missing');
    }

    public function testLoadOnlyPhpFilesUsingFilenameNamespace(): void
    {
        file_put_contents($this->configPath . DIRECTORY_SEPARATOR . 'app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Demo'];\n");
        file_put_contents($this->configPath . DIRECTORY_SEPARATOR . 'database.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['host' => '127.0.0.1'];\n");
        file_put_contents($this->configPath . DIRECTORY_SEPARATOR . 'readme.txt', 'ignored');

        $configs = (new ConfigLoader())->load($this->configPath);

        $this->assertSame([
            'app' => ['name' => 'Demo'],
            'database' => ['host' => '127.0.0.1'],
        ], $configs);
    }

    public function testLoadOrderIsStable(): void
    {
        file_put_contents($this->configPath . DIRECTORY_SEPARATOR . 'z.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn [];\n");
        file_put_contents($this->configPath . DIRECTORY_SEPARATOR . 'a.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn [];\n");
        file_put_contents($this->configPath . DIRECTORY_SEPARATOR . 'm.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn [];\n");

        $configs = (new ConfigLoader())->load($this->configPath);

        $this->assertSame(['a', 'm', 'z'], array_keys($configs));
    }

    public function testLoaderDoesNotScanRecursively(): void
    {
        mkdir($this->configPath . DIRECTORY_SEPARATOR . 'nested');
        file_put_contents($this->configPath . DIRECTORY_SEPARATOR . 'app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Demo'];\n");
        file_put_contents($this->configPath . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'hidden.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['loaded' => true];\n");

        $configs = (new ConfigLoader())->load($this->configPath);

        $this->assertSame(['app' => ['name' => 'Demo']], $configs);
    }

    public function testLoadFileValidatesFileExists(): void
    {
        $this->expectException(ConfigNotFoundException::class);

        (new ConfigLoader())->loadFile($this->configPath . DIRECTORY_SEPARATOR . 'missing.php');
    }

    public function testLoadFileValidatesReturnArray(): void
    {
        $file = $this->configPath . DIRECTORY_SEPARATOR . 'invalid.php';
        file_put_contents($file, "<?php\n\ndeclare(strict_types=1);\n\nreturn 'invalid';\n");

        $this->expectException(ConfigException::class);

        (new ConfigLoader())->loadFile($file);
    }

    public function testLoadFileReturnsArray(): void
    {
        $file = $this->configPath . DIRECTORY_SEPARATOR . 'app.php';
        file_put_contents($file, "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Demo'];\n");

        $this->assertSame(['name' => 'Demo'], (new ConfigLoader())->loadFile($file));
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory) ?: [], ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
