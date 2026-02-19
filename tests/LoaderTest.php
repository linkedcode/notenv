<?php

use PHPUnit\Framework\TestCase;
use Linkedcode\NotEnv\Loader;
use Linkedcode\NotEnv\Config;

final class LoaderTest extends TestCase
{
    private string $configPath;

    protected function setUp(): void
    {
        $this->configPath = __DIR__ . '/config';
        // Limpiamos cache si existe
        $cacheFile = __DIR__ . '/cache/config.php';
        if (file_exists($cacheFile)) unlink($cacheFile);
        if (!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0777, true);
    }

    public function testLoaderMergeAndCache(): void
    {
        $config = Loader::load(__DIR__); // usa tests/config + tests/cache
        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals('TestApp', $config->get('app.name'));      // common
        $this->assertTrue($config->get('app.debug'));                  // config.php
        $this->assertEquals('127.0.0.1', $config->get('database.host'));
        $this->assertEquals(3306, $config->get('database.port'));

        // Cache generado
        $this->assertFileExists(__DIR__ . '/var/cache/config.php');

        // Carga desde cache
        $configCached = Loader::load(__DIR__);
        $this->assertEquals($config->all(), $configCached->all());
    }

    protected function tearDown(): void
    {
        // Limpiamos cache
        $cacheFile = __DIR__ . '/cache/config.php';
        if (file_exists($cacheFile)) unlink($cacheFile);
    }
}
