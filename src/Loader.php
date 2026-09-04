<?php

namespace Linkedcode\NotEnv;

final class Loader
{
    /**
     * Arma la configuración mergeando, en orden de precedencia creciente:
     *
     *   1. config/common.php            base versionada, obligatoria
     *   2. config/config.<env>.php      overrides del entorno, versionable
     *   3. config/config.php            overrides de la máquina, en .gitignore
     *
     * Los dos últimos son opcionales. La cascada de dos archivos alcanzaba
     * mientras cada entorno fuera una máquina distinta; con dev y test
     * conviviendo en la misma, config.php no puede describir a los dos y hace
     * falta un archivo por entorno.
     *
     * El entorno sale de APP_ENV salvo que se pase explícito. Se mira $_ENV y
     * también getenv(): $_ENV sólo se puebla si variables_order incluye "E",
     * que no es el default de PHP, y getenv() no ve lo que otro código escribió
     * en $_ENV (como hace phpunit.xml). Son dos almacenes separados y el valor
     * puede estar en cualquiera de los dos.
     */
    public static function load(string $basePath, ?string $env = null): Config
    {
        $configPath = rtrim($basePath, '/') . '/config';

        $commonFile = $configPath . '/common.php';

        if (!file_exists($commonFile)) {
            throw new \RuntimeException("Archivo common.php no encontrado en config/");
        }

        $config = require $commonFile;

        if ($env === null) {
            $env = $_ENV['APP_ENV'] ?? null;

            if ($env === null || $env === '') {
                $fromGetenv = getenv('APP_ENV');
                $env = $fromGetenv === false ? null : $fromGetenv;
            }
        }

        $overrides = [];

        // Un APP_ENV vacío o con separadores de path daría nombres de archivo
        // inesperados: se ignora en vez de intentar cargarlos.
        if (is_string($env) && $env !== '' && !preg_match('#[/\\\\.]#', $env)) {
            $overrides[] = "config.{$env}.php";
        }

        // config.php va último: es la máquina concreta, y pisa al entorno.
        $overrides[] = 'config.php';

        foreach ($overrides as $file) {
            $path = $configPath . '/' . $file;

            if (file_exists($path)) {
                $config = Merger::merge($config, require $path);
            }
        }

        return new Config($config);
    }
}
