<?php

declare(strict_types=1);

namespace Lukman\Config\Tests;

use Lukman\Config\Config;
use Lukman\Config\ConfigCache;
use Lukman\Config\ConfigLoader;
use Lukman\Config\EnvLoader;
use Lukman\Config\Exception\ConfigException;
use Lukman\Config\Exception\ConfigNotFoundException;
use Lukman\Config\Repository;
use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase
{
    public function testConfigClassCanBeAutoloaded(): void
    {
        $this->assertTrue(class_exists(Config::class));
        $this->assertInstanceOf(Config::class, new Config());
    }

    public function testRepositoryClassCanBeAutoloaded(): void
    {
        $this->assertTrue(class_exists(Repository::class));
        $this->assertInstanceOf(Repository::class, new Repository());
    }

    public function testEnvLoaderClassCanBeAutoloaded(): void
    {
        $this->assertTrue(class_exists(EnvLoader::class));
        $this->assertInstanceOf(EnvLoader::class, new EnvLoader());
    }

    public function testConfigLoaderClassCanBeAutoloaded(): void
    {
        $this->assertTrue(class_exists(ConfigLoader::class));
        $this->assertInstanceOf(ConfigLoader::class, new ConfigLoader());
    }

    public function testConfigCacheClassCanBeAutoloaded(): void
    {
        $this->assertTrue(class_exists(ConfigCache::class));
        $this->assertInstanceOf(ConfigCache::class, new ConfigCache());
    }

    public function testExceptionClassesCanBeAutoloaded(): void
    {
        $this->assertTrue(class_exists(ConfigException::class));
        $this->assertTrue(class_exists(ConfigNotFoundException::class));
    }
}
