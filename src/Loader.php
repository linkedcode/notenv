<?php

namespace Linkedcode\NotEnv;

use Linkedcode\NotEnv\Cache\FileCache;

final class Loader
{
    public static function load(string $basePath): Config
    {
        $configPath = rtrim($basePath, '/') . '/config';
        $cachePath  = rtrim($basePath, '/') . '/var/cache';

        $commonFile = $configPath . '/common.php';
        $activeFile = $configPath . '/config.php';

        if (!file_exists($commonFile)) {
            throw new \RuntimeException("Archivo common.php no encontrado en config/");
        }
        if (!file_exists($activeFile)) {
            throw new \RuntimeException("Archivo config.php no encontrado en config/");
        }

        $cache = new FileCache($cachePath, 'config');
        if ($cache->exists()) {
            return new Config($cache->load());
        }

        $common = require $commonFile;
        $active = require $activeFile;

        $merged = Merger::merge($common, $active);
        $cache->store($merged);

        return new Config($merged);
    }
}
