<?php

declare(strict_types=1);

namespace Lukman\Config\Tests;

use Lukman\Config\Config;
use Lukman\Config\ConfigCache;
use Lukman\Config\Exception\ConfigException;
use Lukman\Config\Exception\ConfigNotFoundException;
use PHPUnit\Framework\TestCase;

class ConfigCacheTest extends TestCase
{
    private string $tempPath;
    private string $cachePath;

    protected function setUp(): void
    {
        $this->tempPath = __DIR__ . DIRECTORY_SEPARATOR . 'cache-temp';
        $this->cachePath = $this->tempPath . DIRECTORY_SEPARATOR . 'config.php';
        mkdir($this->tempPath);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPath);
    }

    public function testWriteCreatesValidPhpFileReturningArray(): void
    {
        $cache = new ConfigCache();

        $cache->write($this->cachePath, ['app' => ['name' => 'Demo']]);

        $this->assertFileExists($this->cachePath);
        $this->assertSame(['app' => ['name' => 'Demo']], require $this->cachePath);
    }

    public function testReadThrowsWhenFileMissing(): void
    {
        $this->expectException(ConfigNotFoundException::class);

        (new ConfigCache())->read($this->cachePath);
    }

    public function testReadRejectsInvalidCache(): void
    {
        file_put_contents($this->cachePath, "<?php\n\ndeclare(strict_types=1);\n\nreturn 'invalid';\n");

        $this->expectException(ConfigException::class);

        (new ConfigCache())->read($this->cachePath);
    }

    public function testExistsChecksFileOnly(): void
    {
        $cache = new ConfigCache();

        $this->assertFalse($cache->exists($this->cachePath));
        $this->assertFalse($cache->exists($this->tempPath));

        $cache->write($this->cachePath, []);

        $this->assertTrue($cache->exists($this->cachePath));
    }

    public function testClearDoesNotErrorWhenFileMissing(): void
    {
        $cache = new ConfigCache();

        $cache->clear($this->cachePath);
        $this->assertFalse($cache->exists($this->cachePath));
    }

    public function testConfigCacheToSavesRepositoryItems(): void
    {
        $config = new Config();
        $result = $config->set('app.name', 'Demo')->cacheTo($this->cachePath);

        $this->assertSame($config, $result);
        $this->assertSame(['app' => ['name' => 'Demo']], (new ConfigCache())->read($this->cachePath));
    }

    public function testConfigLoadCacheFillsRepository(): void
    {
        (new ConfigCache())->write($this->cachePath, ['app' => ['name' => 'Demo']]);

        $config = new Config();
        $result = $config->loadCache($this->cachePath);

        $this->assertSame($config, $result);
        $this->assertSame('Demo', $config->get('app.name'));
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
