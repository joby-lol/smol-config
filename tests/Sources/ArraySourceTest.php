<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use PHPUnit\Framework\TestCase;

class ArraySourceTest extends TestCase
{

    private \Joby\Smol\Config\Sources\ArraySource $source;

    protected function setUp(): void
    {
        $this->source = new \Joby\Smol\Config\Sources\ArraySource();
    }

    public function test_has_returns_false_for_empty_source(): void
    {
        $this->assertFalse($this->source->has('NONEXISTENT_KEY'));
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $this->source['TEST_KEY'] = 'test_value';
        $this->assertTrue($this->source->has('TEST_KEY'));
    }

    public function test_has_returns_false_when_key_does_not_exist(): void
    {
        $this->source['EXISTING_KEY'] = 'value';
        $this->assertFalse($this->source->has('NONEXISTENT_KEY'));
    }

    public function test_get_returns_value_when_key_exists(): void
    {
        $this->source['TEST_KEY'] = 'test_value';
        $this->assertSame('test_value', $this->source->get('TEST_KEY'));
    }

    public function test_get_returns_different_value_types(): void
    {
        $this->source['STRING_KEY'] = 'string_value';
        $this->source['INT_KEY'] = 42;
        $this->source['BOOL_KEY'] = true;
        $this->source['NULL_KEY'] = null;
        $this->source['ARRAY_KEY'] = ['nested' => 'array'];

        $this->assertSame('string_value', $this->source->get('STRING_KEY'));
        $this->assertSame(42, $this->source->get('INT_KEY'));
        $this->assertTrue($this->source->get('BOOL_KEY'));
        $this->assertNull($this->source->get('NULL_KEY'));
        $this->assertSame(['nested' => 'array'], $this->source->get('ARRAY_KEY'));
    }

    public function test_array_access_offsetExists(): void
    {
        $this->source['TEST_KEY'] = 'value';
        $this->assertTrue(isset($this->source['TEST_KEY']));
        $this->assertFalse(isset($this->source['NONEXISTENT_KEY']));
    }

    public function test_array_access_offsetGet(): void
    {
        $this->source['TEST_KEY'] = 'test_value';
        $this->assertSame('test_value', $this->source['TEST_KEY']);
    }

    public function test_array_access_offsetSet(): void
    {
        $this->source['NEW_KEY'] = 'new_value';
        $this->assertSame('new_value', $this->source['NEW_KEY']);

        // Update existing key
        $this->source['NEW_KEY'] = 'updated_value';
        $this->assertSame('updated_value', $this->source['NEW_KEY']);
    }

    public function test_array_access_offsetUnset(): void
    {
        $this->source['TEST_KEY'] = 'value';
        $this->assertTrue(isset($this->source['TEST_KEY']));

        unset($this->source['TEST_KEY']);
        $this->assertFalse(isset($this->source['TEST_KEY']));
    }

    public function test_array_access_converts_offset_to_string(): void
    {
        $this->source[123] = 'numeric_key_value';
        $this->assertTrue(isset($this->source['123']));
        $this->assertSame('numeric_key_value', $this->source['123']);
    }

    public function test_multiple_keys_can_be_set_and_retrieved(): void
    {
        $this->source['KEY1'] = 'value1';
        $this->source['KEY2'] = 'value2';
        $this->source['KEY3'] = 'value3';

        $this->assertTrue($this->source->has('KEY1'));
        $this->assertTrue($this->source->has('KEY2'));
        $this->assertTrue($this->source->has('KEY3'));

        $this->assertSame('value1', $this->source->get('KEY1'));
        $this->assertSame('value2', $this->source->get('KEY2'));
        $this->assertSame('value3', $this->source->get('KEY3'));
    }

}
