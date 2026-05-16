<?php

namespace Linkedcode\NotEnv\Cache;

final class FileCache
{
    private string $file;

    public function __construct(string $cachePath, string $name = 'config')
    {
        if (!is_dir($cachePath)) mkdir($cachePath, 0777, true);
        $this->file = rtrim($cachePath, '/') . "/$name.php";
    }

    public function exists(): bool
    {
        return file_exists($this->file);
    }

    public function load(): array
    {
        return require $this->file;
    }

    public function store(array $config): void
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($this->file, $content);
    }

    public function clear(): void
    {
        if (file_exists($this->file)) {
            file_put_contents($this->file, "<?php\n\nreturn [];\n");
        }
    }
}
