<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use PHPUnit\Framework\TestCase;

class ServerSourceTest extends TestCase
{

    private \Joby\Smol\Config\Sources\ServerSource $source;

    protected function setUp(): void
    {
        $this->source = new \Joby\Smol\Config\Sources\ServerSource();
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $_SERVER['TEST_KEY'] = 'test_value';
        $this->assertTrue($this->source->has('TEST_KEY'));
        unset($_SERVER['TEST_KEY']);
    }

    public function test_has_returns_false_when_key_does_not_exist(): void
    {
        $this->assertFalse($this->source->has('NONEXISTENT_KEY'));
    }

    public function test_get_returns_value_when_key_exists(): void
    {
        $_SERVER['TEST_KEY'] = 'test_value';
        $this->assertSame('test_value', $this->source->get('TEST_KEY'));
        unset($_SERVER['TEST_KEY']);
    }

    public function test_get_returns_different_value_types(): void
    {
        $_SERVER['STRING_KEY'] = 'string_value';
        $_SERVER['INT_KEY'] = 42;
        $_SERVER['BOOL_KEY'] = true;
        $_SERVER['NULL_KEY'] = null;
        $_SERVER['ARRAY_KEY'] = ['nested' => 'array'];

        $this->assertSame('string_value', $this->source->get('STRING_KEY'));
        $this->assertSame(42, $this->source->get('INT_KEY'));
        $this->assertTrue($this->source->get('BOOL_KEY'));
        $this->assertNull($this->source->get('NULL_KEY'));
        $this->assertSame(['nested' => 'array'], $this->source->get('ARRAY_KEY'));

        unset($_SERVER['STRING_KEY'], $_SERVER['INT_KEY'], $_SERVER['BOOL_KEY'], $_SERVER['NULL_KEY'], $_SERVER['ARRAY_KEY']);
    }

}
