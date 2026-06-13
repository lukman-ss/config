<?php

declare(strict_types=1);

namespace Lukman\Config\Tests;

use Lukman\Config\Exception\ConfigException;
use Lukman\Config\Exception\ConfigNotFoundException;
use Lukman\Config\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function testGetReturnsValueUsingDotNotation(): void
    {
        $repository = new Repository([
            'database' => [
                'connections' => [
                    'mysql' => [
                        'host' => '127.0.0.1',
                    ],
                ],
            ],
        ]);

        $this->assertSame('127.0.0.1', $repository->get('database.connections.mysql.host'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $repository = new Repository(['app' => ['name' => 'Demo']]);

        $this->assertSame('fallback', $repository->get('app.env', 'fallback'));
        $this->assertNull($repository->get('missing'));
    }

    public function testSetCreatesNestedArray(): void
    {
        $repository = new Repository();

        $repository->set('app.providers.core', 'Provider');

        $this->assertSame([
            'app' => [
                'providers' => [
                    'core' => 'Provider',
                ],
            ],
        ], $repository->all());
    }

    public function testHasReturnsTrueForExistingNullValue(): void
    {
        $repository = new Repository([
            'app' => null,
            'database' => [
                'host' => null,
            ],
        ]);

        $this->assertTrue($repository->has('app'));
        $this->assertTrue($repository->has('database.host'));
        $this->assertFalse($repository->has('database.port'));
    }

    public function testForgetRemovesNestedKey(): void
    {
        $repository = new Repository([
            'app' => [
                'name' => 'Demo',
                'env' => 'local',
            ],
        ]);

        $repository->forget('app.env');

        $this->assertFalse($repository->has('app.env'));
        $this->assertSame(['app' => ['name' => 'Demo']], $repository->all());
    }

    public function testReplaceChangesAllItems(): void
    {
        $repository = new Repository(['old' => 'value']);

        $repository->replace(['new' => 'value']);

        $this->assertFalse($repository->has('old'));
        $this->assertSame(['new' => 'value'], $repository->all());
    }

    public function testTypedGettersReturnMatchingValues(): void
    {
        $repository = new Repository([
            'string' => 'value',
            'int' => 10,
            'float' => 1.5,
            'bool' => false,
            'array' => ['value'],
            'null' => null,
        ]);

        $this->assertSame('value', $repository->string('string'));
        $this->assertSame(10, $repository->int('int'));
        $this->assertSame(1.5, $repository->float('float'));
        $this->assertFalse($repository->bool('bool'));
        $this->assertSame(['value'], $repository->array('array'));
        $this->assertNull($repository->string('null'));
    }

    public function testTypedGettersReturnDefaultForMissingKey(): void
    {
        $repository = new Repository();

        $this->assertSame('default', $repository->string('missing', 'default'));
        $this->assertSame(10, $repository->int('missing', 10));
        $this->assertSame(1.5, $repository->float('missing', 1.5));
        $this->assertTrue($repository->bool('missing', true));
        $this->assertSame(['default'], $repository->array('missing', ['default']));
    }

    public function testStringGetterThrowsOnMismatch(): void
    {
        $this->expectException(ConfigException::class);

        (new Repository(['key' => 1]))->string('key');
    }

    public function testIntGetterThrowsOnMismatch(): void
    {
        $this->expectException(ConfigException::class);

        (new Repository(['key' => '1']))->int('key');
    }

    public function testFloatGetterThrowsOnMismatch(): void
    {
        $this->expectException(ConfigException::class);

        (new Repository(['key' => 1]))->float('key');
    }

    public function testBoolGetterThrowsOnStringValue(): void
    {
        $this->expectException(ConfigException::class);

        (new Repository(['key' => 'true']))->bool('key');
    }

    public function testArrayGetterThrowsOnMismatch(): void
    {
        $this->expectException(ConfigException::class);

        (new Repository(['key' => 'value']))->array('key');
    }

    public function testRequiredThrowsWhenMissing(): void
    {
        $this->expectException(ConfigNotFoundException::class);

        (new Repository())->required('missing');
    }

    public function testRequiredAllowsExistingNullValue(): void
    {
        $repository = new Repository(['key' => null]);

        $this->assertNull($repository->required('key'));
    }

    public function testMergeCombinesNestedArraysAndOverridesScalars(): void
    {
        $repository = new Repository([
            'app' => [
                'name' => 'Old',
                'debug' => false,
            ],
            'database' => [
                'host' => 'localhost',
            ],
        ]);

        $repository->merge([
            'app' => [
                'name' => 'New',
            ],
            'database' => [
                'port' => 3306,
            ],
            'cache' => 'file',
        ]);

        $this->assertSame([
            'app' => [
                'name' => 'New',
                'debug' => false,
            ],
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
            'cache' => 'file',
        ], $repository->all());
    }

    public function testDefaultsFillsMissingKeysWithoutOverwritingExistingValues(): void
    {
        $repository = new Repository([
            'app' => [
                'name' => 'Existing',
            ],
        ]);

        $repository->defaults([
            'app' => [
                'name' => 'Default',
                'debug' => true,
            ],
            'cache' => 'file',
        ]);

        $this->assertSame([
            'app' => [
                'name' => 'Existing',
                'debug' => true,
            ],
            'cache' => 'file',
        ], $repository->all());
    }

    public function testPushCreatesArrayForMissingKeyAndAppendsValue(): void
    {
        $repository = new Repository(['providers' => ['First']]);

        $repository->push('providers', 'Second');
        $repository->push('aliases', 'Alias');

        $this->assertSame(['First', 'Second'], $repository->get('providers'));
        $this->assertSame(['Alias'], $repository->get('aliases'));
    }

    public function testPrependCreatesArrayForMissingKeyAndPrependsValue(): void
    {
        $repository = new Repository(['providers' => ['Second']]);

        $repository->prepend('providers', 'First');
        $repository->prepend('aliases', 'Alias');

        $this->assertSame(['First', 'Second'], $repository->get('providers'));
        $this->assertSame(['Alias'], $repository->get('aliases'));
    }

    public function testPushThrowsForNonArrayValue(): void
    {
        $this->expectException(ConfigException::class);

        (new Repository(['providers' => 'Provider']))->push('providers', 'Other');
    }

    public function testPrependThrowsForNonArrayValue(): void
    {
        $this->expectException(ConfigException::class);

        (new Repository(['providers' => 'Provider']))->prepend('providers', 'Other');
    }

    public function testDefaultStateIsNotFrozen(): void
    {
        $this->assertFalse((new Repository())->frozen());
    }

    public function testFreezeChangesStateAndGettersStillWork(): void
    {
        $repository = new Repository(['app' => ['name' => 'Demo']]);

        $repository->freeze();

        $this->assertTrue($repository->frozen());
        $this->assertSame('Demo', $repository->get('app.name'));
        $this->assertTrue($repository->has('app.name'));
        $this->assertSame(['app' => ['name' => 'Demo']], $repository->all());
    }

    public function testUnfreezeAllowsMutationAgain(): void
    {
        $repository = new Repository();
        $repository->freeze();
        $repository->unfreeze();

        $repository->set('app.name', 'Demo');

        $this->assertFalse($repository->frozen());
        $this->assertSame('Demo', $repository->get('app.name'));
    }

    public function testSetIsBlockedWhenFrozen(): void
    {
        $this->expectException(ConfigException::class);

        $repository = new Repository();
        $repository->freeze();
        $repository->set('key', 'value');
    }

    public function testForgetIsBlockedWhenFrozen(): void
    {
        $this->expectException(ConfigException::class);

        $repository = new Repository(['key' => 'value']);
        $repository->freeze();
        $repository->forget('key');
    }

    public function testReplaceIsBlockedWhenFrozen(): void
    {
        $this->expectException(ConfigException::class);

        $repository = new Repository();
        $repository->freeze();
        $repository->replace(['key' => 'value']);
    }

    public function testMergeIsBlockedWhenFrozen(): void
    {
        $this->expectException(ConfigException::class);

        $repository = new Repository();
        $repository->freeze();
        $repository->merge(['key' => 'value']);
    }

    public function testDefaultsIsBlockedWhenFrozen(): void
    {
        $this->expectException(ConfigException::class);

        $repository = new Repository();
        $repository->freeze();
        $repository->defaults(['key' => 'value']);
    }

    public function testPushIsBlockedWhenFrozen(): void
    {
        $this->expectException(ConfigException::class);

        $repository = new Repository(['items' => []]);
        $repository->freeze();
        $repository->push('items', 'value');
    }

    public function testPrependIsBlockedWhenFrozen(): void
    {
        $this->expectException(ConfigException::class);

        $repository = new Repository(['items' => []]);
        $repository->freeze();
        $repository->prepend('items', 'value');
    }
}
