<?php

namespace Linkedcode\NotEnv;

final class Loader
{
    /** Los únicos entornos válidos. */
    public const ENVIRONMENTS = ['dev', 'test', 'prod'];

    /**
     * Formas largas que se aceptan por comodidad y se normalizan a la corta.
     * 'prod' es más breve y se lee igual en castellano que en inglés.
     */
    private const ALIASES = [
        'production'  => 'prod',
        'develop'     => 'dev',
        'development' => 'dev',
        'testing'     => 'test',
    ];

    /**
     * Arma la configuración con dos archivos:
     *
     *   1. config/common.php         base común, versionada, OBLIGATORIA
     *   2. config/config.<env>.php   overrides del entorno activo, opcional
     *
     * common.php es el archivo principal: la configuración de verdad vive ahí,
     * estructurada y con todas las claves. Es lo que hace innecesario dotenv.
     * El segundo sólo aporta lo que cambia en ese entorno.
     *
     * Se carga UN solo config.<env>.php, el que corresponde a APP_ENV. Los
     * demás no se leen, así que no pueden pisarse entre sí: un config.dev.php
     * no puede llevarse puesta la base de config.test.php aunque convivan en
     * la misma máquina, que es justo el caso que no cubría el viejo
     * config.php sin sufijo.
     *
     * El entorno sale de APP_ENV salvo que se pase explícito. Se mira $_ENV y
     * también getenv(): $_ENV sólo se puebla si variables_order incluye "E",
     * que no es el default de PHP, y getenv() no ve lo que otro código escribió
     * en $_ENV (como hace phpunit.xml). Son dos almacenes separados y el valor
     * puede estar en cualquiera de los dos.
     *
     * @throws \RuntimeException si falta common.php o si el entorno no es uno
     *                           de ENVIRONMENTS
     */
    public static function load(string $basePath, ?string $env = null): Config
    {
        $configPath = rtrim($basePath, '/') . '/config';

        $commonFile = $configPath . '/common.php';

        if (!file_exists($commonFile)) {
            throw new \RuntimeException("Archivo common.php no encontrado en config/");
        }

        $config = require $commonFile;

        $envFile = $configPath . '/config.' . self::resolveEnv($env) . '.php';

        if (file_exists($envFile)) {
            $config = Merger::merge($config, require $envFile);
        }

        return new Config($config);
    }

    /**
     * Sin APP_ENV el entorno es 'dev': es el único que se usa sin configurar
     * nada. Un valor desconocido es un error y no un default silencioso --
     * caer a 'dev' en un servidor dejaría debug activo y cookies inseguras.
     */
    public static function resolveEnv(?string $env = null): string
    {
        if ($env === null || $env === '') {
            $env = $_ENV['APP_ENV'] ?? null;
        }

        if ($env === null || $env === '') {
            $fromGetenv = getenv('APP_ENV');
            $env = $fromGetenv === false ? null : $fromGetenv;
        }

        if ($env === null || $env === '') {
            return 'dev';
        }

        $env = strtolower(trim($env));
        $env = self::ALIASES[$env] ?? $env;

        if (!in_array($env, self::ENVIRONMENTS, true)) {
            throw new \RuntimeException(sprintf(
                "APP_ENV inválido: '%s'. Los entornos válidos son: %s.",
                $env,
                implode(', ', self::ENVIRONMENTS)
            ));
        }

        return $env;
    }
}
