<?php

use PHPUnit\Framework\TestCase;
use Linkedcode\NotEnv\Merger;

final class MergerTest extends TestCase
{
    public function testOverrideAddsNewKeys(): void
    {
        $result = Merger::merge(['a' => 1], ['b' => 2]);
        $this->assertEquals(['a' => 1, 'b' => 2], $result);
    }

    public function testOverrideReplacesScalarValue(): void
    {
        $result = Merger::merge(['a' => 1], ['a' => 2]);
        $this->assertEquals(['a' => 2], $result);
    }

    public function testOverrideMergesNestedArraysRecursively(): void
    {
        $base = ['database' => ['host' => 'localhost', 'port' => 3306]];
        $override = ['database' => ['host' => '127.0.0.1']];

        $result = Merger::merge($base, $override);

        $this->assertEquals(
            ['database' => ['host' => '127.0.0.1', 'port' => 3306]],
            $result
        );
    }

    public function testOverrideReplacesArrayWithScalar(): void
    {
        $base = ['a' => ['nested' => true]];
        $override = ['a' => 'scalar'];

        $result = Merger::merge($base, $override);

        $this->assertEquals(['a' => 'scalar'], $result);
    }

    public function testBaseIsNotMutated(): void
    {
        $base = ['a' => ['b' => 1]];
        Merger::merge($base, ['a' => ['b' => 2]]);

        $this->assertEquals(['a' => ['b' => 1]], $base);
    }

    public function testEmptyOverrideReturnsBaseUnchanged(): void
    {
        $base = ['a' => 1, 'b' => ['c' => 2]];
        $this->assertEquals($base, Merger::merge($base, []));
    }
}
