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
        $this->assertTrue($config->get('app.debug'));                  // config.dev.php
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

    public function testDefaultsToDevWhenNoEnvIsSet(): void
    {
        $config = Loader::load(self::CASCADE);

        $this->assertEquals('cascade_dev', $config->get('db.dbname'));
    }

    public function testEnvFileIsMergedOnTopOfCommon(): void
    {
        $_ENV['APP_ENV'] = 'test';

        $config = Loader::load(self::CASCADE);

        $this->assertEquals('test', $config->get('app.env'));
        $this->assertEquals(3307, $config->get('db.port'));
        $this->assertEquals('cascade_test', $config->get('db.dbname'));
        // Sin override en ese entorno: sobrevive lo de common.
        $this->assertEquals('Cascade', $config->get('app.name'));
        $this->assertEquals('localhost', $config->get('db.host'));
    }

    /**
     * El punto de todo esto: dev y test conviven en la misma máquina y no se
     * pisan, porque sólo se carga el archivo del entorno activo.
     */
    public function testOnlyTheActiveEnvFileIsLoaded(): void
    {
        $_ENV['APP_ENV'] = 'test';
        $test = Loader::load(self::CASCADE);

        $_ENV['APP_ENV'] = 'dev';
        $dev = Loader::load(self::CASCADE);

        $this->assertEquals('cascade_test', $test->get('db.dbname'));
        $this->assertEquals('cascade_dev', $dev->get('db.dbname'));

        // config.dev.php define app.debug; config.test.php no, y no lo hereda.
        $this->assertTrue($dev->get('app.debug'));
        $this->assertFalse($test->get('app.debug'));
    }

    public function testExplicitEnvArgumentOverridesTheEnvironment(): void
    {
        $_ENV['APP_ENV'] = 'dev';

        $config = Loader::load(self::CASCADE, 'test');

        $this->assertEquals('cascade_test', $config->get('db.dbname'));
    }

    public function testMissingEnvFileIsNotAnError(): void
    {
        $_ENV['APP_ENV'] = 'prod';

        $config = Loader::load(self::CASCADE);

        // No hay config.prod.php en el fixture: queda common tal cual.
        $this->assertEquals('cascade', $config->get('db.dbname'));
        $this->assertEquals(3306, $config->get('db.port'));
    }

    public function testUnknownEnvIsRejected(): void
    {
        $_ENV['APP_ENV'] = 'staging';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("APP_ENV inválido: 'staging'");

        Loader::load(self::CASCADE);
    }

    /** @return array<string, array{string, string}> */
    public static function aliasProvider(): array
    {
        return [
            'production' => ['production', 'prod'],
            'PROD'       => ['PROD', 'prod'],
            'testing'    => ['testing', 'test'],
            'development'=> ['development', 'dev'],
            'con espacios' => ['  prod  ', 'prod'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('aliasProvider')]
    public function testEnvAliasesAreNormalized(string $given, string $expected): void
    {
        $_ENV['APP_ENV'] = $given;

        $this->assertEquals($expected, Loader::resolveEnv());
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
     * La lista blanca de entornos deja fuera, de paso, cualquier intento de
     * armar un path hacia otro directorio.
     */
    public function testEnvWithPathSeparatorsIsRejected(): void
    {
        $_ENV['APP_ENV'] = '../../etc/passwd';

        $this->expectException(\RuntimeException::class);

        Loader::load(self::CASCADE);
    }
}
