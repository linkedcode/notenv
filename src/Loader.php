<?php

namespace Linkedcode\NotEnv;

final class Loader
{
    public static function load(string $basePath): Config
    {
        $configPath = rtrim($basePath, '/') . '/config';

        $commonFile = $configPath . '/common.php';
        $activeFile = $configPath . '/config.php';

        if (!file_exists($commonFile)) {
            throw new \RuntimeException("Archivo common.php no encontrado en config/");
        }

        $common = require $commonFile;
        // config.php es opcional: en despliegues (Docker) puede no existir y los
        // overrides venir del entorno, leidos desde common.php.
        $active = file_exists($activeFile) ? require $activeFile : [];

        return new Config(Merger::merge($common, $active));
    }
}
