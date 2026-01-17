<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use PHPUnit\Framework\TestCase;

class EnvSourceTest extends TestCase
{

    private \Joby\Smol\Config\Sources\EnvSource $source;

    protected function setUp(): void
    {
        $this->source = new \Joby\Smol\Config\Sources\EnvSource();
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $_ENV['TEST_KEY'] = 'test_value';
        $this->assertTrue($this->source->has('TEST_KEY'));
        unset($_ENV['TEST_KEY']);
    }

    public function test_has_returns_false_when_key_does_not_exist(): void
    {
        $this->assertFalse($this->source->has('NONEXISTENT_KEY'));
    }

    public function test_get_returns_value_when_key_exists(): void
    {
        $_ENV['TEST_KEY'] = 'test_value';
        $this->assertSame('test_value', $this->source->get('TEST_KEY'));
        unset($_ENV['TEST_KEY']);
    }

    public function test_get_returns_different_value_types(): void
    {
        $_ENV['STRING_KEY'] = 'string_value';
        $_ENV['INT_KEY'] = 42;
        $_ENV['BOOL_KEY'] = true;
        $_ENV['NULL_KEY'] = null;
        $_ENV['ARRAY_KEY'] = ['nested' => 'array'];

        $this->assertSame('string_value', $this->source->get('STRING_KEY'));
        $this->assertSame(42, $this->source->get('INT_KEY'));
        $this->assertTrue($this->source->get('BOOL_KEY'));
        $this->assertNull($this->source->get('NULL_KEY'));
        $this->assertSame(['nested' => 'array'], $this->source->get('ARRAY_KEY'));

        unset($_ENV['STRING_KEY'], $_ENV['INT_KEY'], $_ENV['BOOL_KEY'], $_ENV['NULL_KEY'], $_ENV['ARRAY_KEY']);
    }

}
