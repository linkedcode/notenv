<?php

use PHPUnit\Framework\TestCase;
use Linkedcode\NotEnv\Config;
use Linkedcode\NotEnv\Exception\ConfigNotFoundException;

final class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config([
            'app' => [
                'name' => 'TestApp',
                'debug' => true,
            ],
            'database' => [
                'host' => '127.0.0.1',
                'port' => 3306,
            ],
        ]);
    }

    public function testAllReturnsRawArray(): void
    {
        $this->assertEquals([
            'app' => ['name' => 'TestApp', 'debug' => true],
            'database' => ['host' => '127.0.0.1', 'port' => 3306],
        ], $this->config->all());
    }

    public function testGetNestedValue(): void
    {
        $this->assertEquals('127.0.0.1', $this->config->get('database.host'));
        $this->assertEquals(3306, $this->config->get('database.port'));
    }

    public function testGetTopLevelValue(): void
    {
        $this->assertEquals(['name' => 'TestApp', 'debug' => true], $this->config->get('app'));
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        $this->assertEquals('fallback', $this->config->get('app.missing', 'fallback'));
        $this->assertNull($this->config->get('app.missing', null));
        $this->assertEquals('fallback', $this->config->get('missing.branch.deep', 'fallback'));
    }

    public function testGetThrowsWhenMissingAndNoDefault(): void
    {
        $this->expectException(ConfigNotFoundException::class);
        $this->config->get('app.missing');
    }

    public function testGetThrowsWhenTraversingIntoScalar(): void
    {
        $this->expectException(ConfigNotFoundException::class);
        $this->config->get('app.name.nested');
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->assertTrue($this->config->has('database.host'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->config->has('database.missing'));
    }
}
