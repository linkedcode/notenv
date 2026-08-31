<?php

use PHPUnit\Framework\TestCase;
use Linkedcode\NotEnv\Loader;
use Linkedcode\NotEnv\Config;

final class LoaderTest extends TestCase
{
    public function testLoaderMerge(): void
    {
        $config = Loader::load(__DIR__); // usa tests/config
        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals('TestApp', $config->get('app.name'));      // common
        $this->assertTrue($config->get('app.debug'));                  // config.php
        $this->assertEquals('127.0.0.1', $config->get('database.host'));
        $this->assertEquals(3306, $config->get('database.port'));
    }

    public function testLoadRereadsSourcesOnEveryCall(): void
    {
        $config = Loader::load(__DIR__);
        $reloaded = Loader::load(__DIR__);

        $this->assertEquals($config->all(), $reloaded->all());
    }

    public function testThrowsWhenCommonFileMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('common.php no encontrado');

        Loader::load(__DIR__ . '/fixtures/missing-common');
    }

    public function testLoadsWithoutActiveConfigFile(): void
    {
        $config = Loader::load(__DIR__ . '/fixtures/missing-config');

        $this->assertEquals('SoloCommon', $config->get('app.name'));
    }
}
