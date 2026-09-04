<?php

use PHPUnit\Framework\TestCase;
use Linkedcode\NotEnv\Loader;
use Linkedcode\NotEnv\Config;

final class LoaderTest extends TestCase
{
    private const CASCADE = __DIR__ . '/fixtures/env-cascade';

    /** APP_ENV es estado global: se restaura tal como estaba. */
    private ?string $originalEnv = null;

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV['APP_ENV'] ?? null;
        unset($_ENV['APP_ENV']);
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv === null) {
            unset($_ENV['APP_ENV']);
        } else {
            $_ENV['APP_ENV'] = $this->originalEnv;
        }
    }

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

    public function testEnvFileIsIgnoredWhenNoEnvIsSet(): void
    {
        $config = Loader::load(self::CASCADE);

        // Sin APP_ENV no se carga config.test.php: quedan common + config.php.
        $this->assertEquals('dev', $config->get('app.env'));
        $this->assertEquals(3306, $config->get('db.port'));
        $this->assertEquals('cascade', $config->get('db.dbname'));
    }

    public function testEnvFileIsMergedOnTopOfCommon(): void
    {
        $_ENV['APP_ENV'] = 'test';

        $config = Loader::load(self::CASCADE);

        $this->assertEquals('test', $config->get('app.env'));
        $this->assertEquals(3307, $config->get('db.port'));
        $this->assertEquals('cascade_test', $config->get('db.dbname'));
        // Sin override en ningún lado: sobrevive el de common.
        $this->assertEquals('Cascade', $config->get('app.name'));
    }

    public function testMachineConfigWinsOverEnvFile(): void
    {
        $_ENV['APP_ENV'] = 'test';

        $config = Loader::load(self::CASCADE);

        // config.php es el último de la cascada.
        $this->assertEquals('127.0.0.1', $config->get('db.host'));
        $this->assertTrue($config->get('app.debug'));
    }

    public function testExplicitEnvArgumentOverridesTheEnvironment(): void
    {
        $_ENV['APP_ENV'] = 'dev';

        $config = Loader::load(self::CASCADE, 'test');

        $this->assertEquals('cascade_test', $config->get('db.dbname'));
    }

    public function testMissingEnvFileIsNotAnError(): void
    {
        $_ENV['APP_ENV'] = 'staging';

        $config = Loader::load(self::CASCADE);

        $this->assertEquals('dev', $config->get('app.env'));
        $this->assertEquals('cascade', $config->get('db.dbname'));
    }

    /**
     * En un subproceso (bin/migrate corriendo con VAR=x php ...) el valor llega
     * al entorno real y no a $_ENV, salvo que variables_order incluya "E".
     */
    public function testEnvIsAlsoReadFromGetenv(): void
    {
        putenv('APP_ENV=test');

        try {
            $config = Loader::load(self::CASCADE);

            $this->assertEquals('cascade_test', $config->get('db.dbname'));
        } finally {
            putenv('APP_ENV');
        }
    }

    /**
     * Un APP_ENV con separadores de path no puede terminar cargando un archivo
     * de otro directorio.
     */
    public function testEnvWithPathSeparatorsIsIgnored(): void
    {
        $_ENV['APP_ENV'] = '../../etc/passwd';

        $config = Loader::load(self::CASCADE);

        $this->assertEquals('dev', $config->get('app.env'));
    }
}
