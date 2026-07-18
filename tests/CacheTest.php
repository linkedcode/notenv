<?php

use PHPUnit\Framework\TestCase;
use Linkedcode\NotEnv\Cache\FileCache;

final class CacheTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = __DIR__ . '/var/cache_test';
        $this->removeDir($this->cachePath);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->cachePath);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (glob($dir . '/*') as $file) {
            unlink($file);
        }
        rmdir($dir);
    }

    public function testCreatesCacheDirectoryIfMissing(): void
    {
        $this->assertDirectoryDoesNotExist($this->cachePath);
        new FileCache($this->cachePath);
        $this->assertDirectoryExists($this->cachePath);
    }

    public function testExistsIsFalseBeforeStore(): void
    {
        $cache = new FileCache($this->cachePath, 'config');
        $this->assertFalse($cache->exists());
    }

    public function testStoreAndLoadRoundTrip(): void
    {
        $cache = new FileCache($this->cachePath, 'config');
        $data = ['app' => ['name' => 'TestApp'], 'nested' => ['a' => 1, 'b' => [2, 3]]];

        $cache->store($data);

        $this->assertTrue($cache->exists());
        $this->assertEquals($data, $cache->load());
    }

    public function testClearEmptiesStoredConfigButKeepsFile(): void
    {
        $cache = new FileCache($this->cachePath, 'config');
        $cache->store(['a' => 1]);

        $cache->clear();

        $this->assertTrue($cache->exists());
        $this->assertEquals([], $cache->load());
    }

    public function testClearOnNonExistentFileDoesNothing(): void
    {
        $cache = new FileCache($this->cachePath, 'config');
        $cache->clear();

        $this->assertFalse($cache->exists());
    }

    public function testUsesCustomCacheName(): void
    {
        $cache = new FileCache($this->cachePath, 'custom');
        $cache->store(['x' => 1]);

        $this->assertFileExists($this->cachePath . '/custom.php');
    }
}
