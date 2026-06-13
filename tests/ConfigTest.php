<?php

declare(strict_types=1);

namespace Lukman\Config\Tests;

use Lukman\Config\Config;
use Lukman\Config\ConfigCache;
use Lukman\Config\ConfigLoader;
use Lukman\Config\EnvLoader;
use Lukman\Config\Exception\ConfigNotFoundException;
use Lukman\Config\Repository;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private string $tempPath;

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
        $this->tempPath = __DIR__ . DIRECTORY_SEPARATOR . 'config-temp';
        mkdir($this->tempPath);
        $this->originalEnv = $_ENV;
        $this->originalServer = $_SERVER;
        unset($_ENV['CONFIG_TEST'], $_SERVER['CONFIG_TEST']);
        putenv('CONFIG_TEST');
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPath);
        putenv('CONFIG_TEST');
        $_ENV = $this->originalEnv;
        $_SERVER = $this->originalServer;
    }

    public function testCreatesDefaultDependencies(): void
    {
        $config = new Config();

        $this->assertInstanceOf(Repository::class, $config->repository());
        $this->assertInstanceOf(EnvLoader::class, $config->env());
        $this->assertInstanceOf(ConfigLoader::class, $config->loader());
        $this->assertInstanceOf(ConfigCache::class, $config->cache());
    }

    public function testAcceptsCustomDependencies(): void
    {
        $repository = new Repository(['app' => ['name' => 'Demo']]);
        $env = new EnvLoader();
        $loader = new ConfigLoader();
        $cache = new ConfigCache();

        $config = new Config($repository, $env, $loader, $cache);

        $this->assertSame($repository, $config->repository());
        $this->assertSame($env, $config->env());
        $this->assertSame($loader, $config->loader());
        $this->assertSame($cache, $config->cache());
    }

    public function testLoadEnvCallsEnvLoader(): void
    {
        $envPath = $this->tempPath . DIRECTORY_SEPARATOR . '.env';
        file_put_contents($envPath, 'CONFIG_TEST=loaded');

        $config = new Config();
        $result = $config->loadEnv($envPath);

        $this->assertSame($config, $result);
        $this->assertSame('loaded', $_ENV['CONFIG_TEST']);
        $this->assertSame('loaded', $_SERVER['CONFIG_TEST']);
    }

    public function testLoadDirectoryLoadsDataIntoRepository(): void
    {
        file_put_contents($this->tempPath . DIRECTORY_SEPARATOR . 'app.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ['name' => 'Demo'];\n");

        $config = new Config();
        $result = $config->loadDirectory($this->tempPath);

        $this->assertSame($config, $result);
        $this->assertSame('Demo', $config->get('app.name'));
    }

    public function testRepositoryProxyMethods(): void
    {
        $config = new Config();

        $result = $config->set('app.name', 'Demo');

        $this->assertSame($config, $result);
        $this->assertTrue($config->has('app.name'));
        $this->assertSame('Demo', $config->get('app.name'));
        $this->assertSame('fallback', $config->get('app.env', 'fallback'));
        $this->assertSame(['app' => ['name' => 'Demo']], $config->all());

        $this->assertSame($config, $config->forget('app.name'));
        $this->assertFalse($config->has('app.name'));
    }

    public function testTypedAndRequiredProxyMethods(): void
    {
        $config = new Config();
        $config
            ->set('app.name', 'Demo')
            ->set('app.port', 8080)
            ->set('app.ratio', 1.5)
            ->set('app.debug', true)
            ->set('app.providers', ['Provider'])
            ->set('app.null', null);

        $this->assertSame('Demo', $config->string('app.name'));
        $this->assertSame(8080, $config->int('app.port'));
        $this->assertSame(1.5, $config->float('app.ratio'));
        $this->assertTrue($config->bool('app.debug'));
        $this->assertSame(['Provider'], $config->array('app.providers'));
        $this->assertNull($config->required('app.null'));

        $this->expectException(ConfigNotFoundException::class);
        $config->required('missing');
    }

    public function testMutationProxyMethodsReturnSelf(): void
    {
        $config = new Config();

        $this->assertSame($config, $config->defaults(['app' => ['debug' => false]]));
        $this->assertSame($config, $config->merge(['app' => ['name' => 'Demo']]));
        $this->assertSame($config, $config->push('app.providers', 'Second'));
        $this->assertSame($config, $config->prepend('app.providers', 'First'));

        $this->assertSame([
            'app' => [
                'debug' => false,
                'name' => 'Demo',
                'providers' => ['First', 'Second'],
            ],
        ], $config->all());
    }

    public function testFreezeProxyReturnsSelfAndReflectsState(): void
    {
        $config = new Config();

        $this->assertFalse($config->frozen());
        $this->assertSame($config, $config->freeze());
        $this->assertTrue($config->frozen());
        $this->assertSame($config, $config->unfreeze());
        $this->assertFalse($config->frozen());
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
