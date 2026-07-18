<?php

use PHPUnit\Framework\TestCase;
use Linkedcode\NotEnv\Loader;
use Linkedcode\NotEnv\Config;

final class LoaderTest extends TestCase
{
    private string $configPath;
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->configPath = __DIR__ . '/config';
        $this->cacheFile = __DIR__ . '/var/cache/config.php';
        if (file_exists($this->cacheFile)) unlink($this->cacheFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) unlink($this->cacheFile);
    }

    public function testLoaderMergeAndCache(): void
    {
        $config = Loader::load(__DIR__); // usa tests/config + tests/var/cache
        $this->assertInstanceOf(Config::class, $config);

        $this->assertEquals('TestApp', $config->get('app.name'));      // common
        $this->assertTrue($config->get('app.debug'));                  // config.php
        $this->assertEquals('127.0.0.1', $config->get('database.host'));
        $this->assertEquals(3306, $config->get('database.port'));

        // Cache generado
        $this->assertFileExists($this->cacheFile);

        // Carga desde cache
        $configCached = Loader::load(__DIR__);
        $this->assertEquals($config->all(), $configCached->all());
    }

    public function testLoadUsesCacheInsteadOfRereadingSources(): void
    {
        Loader::load(__DIR__); // genera el cache real

        // Corrompemos el cache manualmente para verificar que load() lo usa tal cual
        file_put_contents($this->cacheFile, "<?php\n\nreturn ['from' => 'cache'];\n");

        $config = Loader::load(__DIR__);

        $this->assertEquals(['from' => 'cache'], $config->all());
    }

    public function testReloadForcesRewriteOfCache(): void
    {
        Loader::load(__DIR__);
        file_put_contents($this->cacheFile, "<?php\n\nreturn ['from' => 'stale-cache'];\n");

        $config = Loader::reload(__DIR__);

        $this->assertEquals('TestApp', $config->get('app.name'));
        $this->assertEquals('TestApp', Loader::load(__DIR__)->get('app.name'));
    }

    public function testThrowsWhenCommonFileMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('common.php no encontrado');

        Loader::load(__DIR__ . '/fixtures/missing-common');
    }

    public function testThrowsWhenActiveConfigFileMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('config.php no encontrado');

        Loader::load(__DIR__ . '/fixtures/missing-config');
    }
}
