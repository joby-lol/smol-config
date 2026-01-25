<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config;

use Joby\Smol\Config\Sources\ArraySource;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{

    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config();
    }

    // ==================== addSource() Tests ====================

    public function test_add_source_creates_new_prefix(): void
    {
        $source = new ArraySource();
        $source['key'] = 'value';

        $this->config->addSource('app', $source);

        $this->assertTrue($this->config->has('app/key'));
        $this->assertSame('value', $this->config->getRaw('app/key'));
    }

    public function test_add_source_appends_to_existing_prefix(): void
    {
        $source1 = new ArraySource();
        $source1['key1'] = 'value1';

        $source2 = new ArraySource();
        $source2['key2'] = 'value2';

        $this->config->addSource('app', $source1);
        $this->config->addSource('app', $source2);

        $this->assertTrue($this->config->has('app/key1'));
        $this->assertTrue($this->config->has('app/key2'));
        $this->assertSame('value1', $this->config->getRaw('app/key1'));
        $this->assertSame('value2', $this->config->getRaw('app/key2'));
    }

    public function test_add_source_maintains_precedence_order(): void
    {
        $source1 = new ArraySource();
        $source1['shared'] = 'from_first';
        $source1['only_first'] = 'value1';

        $source2 = new ArraySource();
        $source2['shared'] = 'from_second';
        $source2['only_second'] = 'value2';

        $this->config->addSource('app', $source1);
        $this->config->addSource('app', $source2);

        // First source should win for shared keys
        $this->assertSame('from_first', $this->config->getRaw('app/shared'));
        // But keys unique to second source should still be accessible
        $this->assertSame('value2', $this->config->getRaw('app/only_second'));
    }

    public function test_add_source_multiple_prefixes(): void
    {
        $appSource = new ArraySource();
        $appSource['name'] = 'MyApp';

        $envSource = new ArraySource();
        $envSource['env'] = 'production';

        $this->config->addSource('app', $appSource);
        $this->config->addSource('env', $envSource);

        $this->assertSame('MyApp', $this->config->getRaw('app/name'));
        $this->assertSame('production', $this->config->getRaw('env/env'));
    }

    public function test_add_source_multiple_times_to_same_prefix(): void
    {
        $source1 = new ArraySource();
        $source1['key'] = 'value1';

        $source2 = new ArraySource();
        $source2['key'] = 'value2';

        $source3 = new ArraySource();
        $source3['key'] = 'value3';

        $this->config->addSource('app', $source1);
        $this->config->addSource('app', $source2);
        $this->config->addSource('app', $source3);

        // First source should win
        $this->assertSame('value1', $this->config->getRaw('app/key'));
    }

    // ==================== getRaw() Tests ====================

    public function test_get_raw_throws_when_prefix_not_found(): void
    {
        $this->expectException(ConfigKeyNotFoundException::class);
        $this->expectExceptionMessage('not found in any source for prefix');

        $this->config->getRaw('nonexistent/key');
    }

    public function test_get_raw_throws_when_no_prefix_in_key(): void
    {
        $this->expectException(ConfigKeyNotFoundException::class);
        $this->expectExceptionMessage('does not include a prefix');

        $this->config->getRaw('keyWithoutPrefix');
    }

    public function test_get_raw_throws_when_key_not_in_any_source(): void
    {
        $this->expectException(ConfigKeyNotFoundException::class);

        $source = new ArraySource();
        $source['existing'] = 'value';
        $this->config->sources['app'] = [$source];

        $this->config->getRaw('app/nonexistent');
    }

    public function test_get_raw_returns_value_from_source(): void
    {
        $source = new ArraySource();
        $source['key'] = 'test_value';
        $this->config->sources['app'] = [$source];

        $this->assertSame('test_value', $this->config->getRaw('app/key'));
    }

    public function test_get_raw_returns_different_types(): void
    {
        $source = new ArraySource();
        $source['string'] = 'text';
        $source['int'] = 42;
        $source['float'] = 3.14;
        $source['bool'] = true;
        $source['null'] = null;
        $source['array'] = ['nested' => 'value'];
        $source['object'] = new \stdClass();

        $this->config->sources['app'] = [$source];

        $this->assertSame('text', $this->config->getRaw('app/string'));
        $this->assertSame(42, $this->config->getRaw('app/int'));
        $this->assertSame(3.14, $this->config->getRaw('app/float'));
        $this->assertTrue($this->config->getRaw('app/bool'));
        $this->assertNull($this->config->getRaw('app/null'));
        $this->assertSame(['nested' => 'value'], $this->config->getRaw('app/array'));
        $this->assertInstanceOf(\stdClass::class, $this->config->getRaw('app/object'));
    }

    public function test_get_raw_uses_first_source_that_has_key(): void
    {
        $source1 = new ArraySource();
        $source1['shared'] = 'from_source1';

        $source2 = new ArraySource();
        $source2['shared'] = 'from_source2';

        $this->config->sources['app'] = [$source1, $source2];

        $this->assertSame('from_source1', $this->config->getRaw('app/shared'));
    }

    // ==================== has() Tests ====================

    public function test_has_returns_false_for_nonexistent_prefix(): void
    {
        $this->assertFalse($this->config->has('app/key'));
    }

    public function test_has_returns_false_when_key_not_in_any_source(): void
    {
        $source = new ArraySource();
        $source['existing'] = 'value';
        $this->config->sources['app'] = [$source];

        $this->assertFalse($this->config->has('app/nonexistent'));
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $source = new ArraySource();
        $source['key'] = 'value';
        $this->config->sources['app'] = [$source];

        $this->assertTrue($this->config->has('app/key'));
    }

    public function test_has_checks_all_sources(): void
    {
        $source1 = new ArraySource();
        $source1['key1'] = 'value1';

        $source2 = new ArraySource();
        $source2['key2'] = 'value2';

        $this->config->sources['app'] = [$source1, $source2];

        $this->assertTrue($this->config->has('app/key1'));
        $this->assertTrue($this->config->has('app/key2'));
    }

    // ==================== getString() Tests ====================

    public function test_get_string_returns_string_value(): void
    {
        $source = new ArraySource();
        $source['key'] = 'string_value';
        $this->config->sources['app'] = [$source];

        $this->assertSame('string_value', $this->config->getString('app/key'));
    }

    public function test_get_string_casts_int_to_string(): void
    {
        $source = new ArraySource();
        $source['key'] = 42;
        $this->config->sources['app'] = [$source];

        $this->assertSame('42', $this->config->getString('app/key'));
    }

    public function test_get_string_casts_float_to_string(): void
    {
        $source = new ArraySource();
        $source['key'] = 3.14;
        $this->config->sources['app'] = [$source];

        $this->assertSame('3.14', $this->config->getString('app/key'));
    }

    public function test_get_string_casts_bool_to_string(): void
    {
        $source = new ArraySource();
        $source['true'] = true;
        $source['false'] = false;
        $this->config->sources['app'] = [$source];

        $this->assertSame('1', $this->config->getString('app/true'));
        $this->assertSame('', $this->config->getString('app/false'));
    }

    public function test_get_string_throws_on_array(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['key'] = ['array' => 'value'];
        $this->config->sources['app'] = [$source];

        $this->config->getString('app/key');
    }

    public function test_get_string_throws_on_object(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['key'] = new \stdClass();
        $this->config->sources['app'] = [$source];

        $this->config->getString('app/key');
    }

    // ==================== getInt() Tests ====================

    public function test_get_int_returns_int_value(): void
    {
        $source = new ArraySource();
        $source['key'] = 42;
        $this->config->sources['app'] = [$source];

        $this->assertSame(42, $this->config->getInt('app/key'));
    }

    public function test_get_int_throws_on_fractional_float(): void
    {
        $source = new ArraySource();
        $source['key'] = 3.99;
        $this->config->sources['app'] = [$source];

        $this->expectException(ConfigTypeException::class);
        $this->config->getInt('app/key');
    }

    public function test_get_int_casts_numeric_string_to_int(): void
    {
        $source = new ArraySource();
        $source['key'] = '42';
        $this->config->sources['app'] = [$source];

        $this->assertSame(42, $this->config->getInt('app/key'));
    }

    public function test_get_int_throws_on_non_numeric_string(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['key'] = 'not_a_number';
        $this->config->sources['app'] = [$source];

        $this->config->getInt('app/key');
    }

    public function test_get_int_throws_on_array(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['key'] = [];
        $this->config->sources['app'] = [$source];

        $this->config->getInt('app/key');
    }

    // ==================== getFloat() Tests ====================

    public function test_get_float_returns_float_value(): void
    {
        $source = new ArraySource();
        $source['key'] = 3.14;
        $this->config->sources['app'] = [$source];

        $this->assertSame(3.14, $this->config->getFloat('app/key'));
    }

    public function test_get_float_casts_int_to_float(): void
    {
        $source = new ArraySource();
        $source['key'] = 42;
        $this->config->sources['app'] = [$source];

        $this->assertSame(42.0, $this->config->getFloat('app/key'));
    }

    public function test_get_float_casts_numeric_string_to_float(): void
    {
        $source = new ArraySource();
        $source['key'] = '3.14';
        $this->config->sources['app'] = [$source];

        $this->assertSame(3.14, $this->config->getFloat('app/key'));
    }

    public function test_get_float_throws_on_non_numeric_string(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['key'] = 'not_a_number';
        $this->config->sources['app'] = [$source];

        $this->config->getFloat('app/key');
    }

    // ==================== getBool() Tests ====================

    public function test_get_bool_returns_bool_value(): void
    {
        $source = new ArraySource();
        $source['true'] = true;
        $source['false'] = false;
        $this->config->sources['app'] = [$source];

        $this->assertTrue($this->config->getBool('app/true'));
        $this->assertFalse($this->config->getBool('app/false'));
    }

    public function test_get_bool_casts_int_to_bool(): void
    {
        $source = new ArraySource();
        $source['zero'] = 0;
        $source['one'] = 1;
        $this->config->sources['app'] = [$source];

        $this->assertFalse($this->config->getBool('app/zero'));
        $this->assertTrue($this->config->getBool('app/one'));
    }

    public function test_get_bool_throws_on_non_zero_non_one_int(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['key'] = 42;
        $this->config->sources['app'] = [$source];

        $this->config->getBool('app/key');
    }

    public function test_get_bool_casts_string_to_bool(): void
    {
        $source = new ArraySource();
        $source['string_true'] = 'true';
        $source['string_1'] = '1';
        $source['string_yes'] = 'yes';
        $source['string_on'] = 'on';
        $source['string_false'] = 'false';
        $source['string_0'] = '0';
        $source['string_no'] = 'no';
        $source['string_off'] = 'off';
        $this->config->sources['test'] = [$source];

        $this->assertTrue($this->config->getBool('test/string_true'));
        $this->assertTrue($this->config->getBool('test/string_1'));
        $this->assertTrue($this->config->getBool('test/string_yes'));
        $this->assertTrue($this->config->getBool('test/string_on'));
        $this->assertFalse($this->config->getBool('test/string_false'));
        $this->assertFalse($this->config->getBool('test/string_0'));
        $this->assertFalse($this->config->getBool('test/string_no'));
        $this->assertFalse($this->config->getBool('test/string_off'));
    }

    public function test_get_bool_casts_float_to_bool(): void
    {
        $source = new ArraySource();
        $source['zero'] = 0.0;
        $source['one'] = 1.0;
        $this->config->sources['app'] = [$source];

        $this->assertFalse($this->config->getBool('app/zero'));
        $this->assertTrue($this->config->getBool('app/one'));
    }

    // ==================== getObject() Tests ====================

    public function test_get_object_returns_object_of_correct_class(): void
    {
        $obj = new \stdClass();
        $source = new ArraySource();
        $source['key'] = $obj;
        $this->config->sources['app'] = [$source];

        $this->assertSame($obj, $this->config->getObject('app/key', \stdClass::class));
    }

    public function test_get_object_throws_on_wrong_type(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['key'] = 'not_an_object';
        $this->config->sources['app'] = [$source];

        $this->config->getObject('app/key', \stdClass::class);
    }

    public function test_get_object_throws_on_wrong_class(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['key'] = new \ArrayIterator();
        $this->config->sources['app'] = [$source];

        $this->config->getObject('app/key', \stdClass::class);
    }

    // ==================== interpolate() Tests ====================

    public function test_interpolate_returns_value_without_placeholders(): void
    {
        $result = $this->config->interpolate('hello world');
        $this->assertSame('hello world', $result);
    }

    public function test_interpolate_replaces_single_placeholder(): void
    {
        $source = new ArraySource();
        $source['name'] = 'World';
        $this->config->sources['app'] = [$source];

        $result = $this->config->interpolate('Hello ${app/name}');
        $this->assertSame('Hello World', $result);
    }

    public function test_interpolate_replaces_multiple_placeholders(): void
    {
        $source = new ArraySource();
        $source['greeting'] = 'Hello';
        $source['name'] = 'World';
        $this->config->sources['app'] = [$source];

        $result = $this->config->interpolate('${app/greeting} ${app/name}');
        $this->assertSame('Hello World', $result);
    }

    public function test_interpolate_casts_values_to_string(): void
    {
        $source = new ArraySource();
        $source['port'] = 8080;
        $source['enabled'] = true;
        $this->config->sources['app'] = [$source];

        $result = $this->config->interpolate('Server on port ${app/port}, enabled: ${app/enabled}');
        $this->assertSame('Server on port 8080, enabled: 1', $result);
    }

    public function test_interpolate_handles_nested_interpolation(): void
    {
        $source = new ArraySource();
        $source['inner'] = 'value';
        $source['outer'] = '${app/inner}';
        $this->config->sources['app'] = [$source];

        $result = $this->config->interpolate('Result: ${app/outer}');
        $this->assertSame('Result: value', $result);
    }

    public function test_interpolate_throws_on_circular_reference(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Circular reference detected');

        $source = new ArraySource();
        $source['a'] = '${app/b}';
        $source['b'] = '${app/a}';
        $this->config->sources['app'] = [$source];

        $this->config->interpolate('${app/a}');
    }

    public function test_interpolate_throws_on_nonexistent_key(): void
    {
        $this->expectException(ConfigKeyNotFoundException::class);

        $this->config->interpolate('${app/nonexistent}');
    }

    public function test_interpolate_throws_on_non_scalar_value(): void
    {
        $this->expectException(ConfigTypeException::class);

        $source = new ArraySource();
        $source['array'] = ['key' => 'value'];
        $this->config->sources['app'] = [$source];

        $this->config->interpolate('${app/array}');
    }

    // ==================== getString with interpolation Tests ====================

    public function test_get_string_with_interpolation(): void
    {
        $source = new ArraySource();
        $source['host'] = 'localhost';
        $source['port'] = 8080;
        $this->config->sources['app'] = [$source];

        $source2 = new ArraySource();
        $source2['database_url'] = 'postgresql://${app/host}:${app/port}/mydb';
        $this->config->sources['config'] = [$source2];

        $result = $this->config->getString('config/database_url');
        $this->assertSame('postgresql://localhost:8080/mydb', $result);
    }

    // ==================== Multiple sources Tests ====================

    public function test_different_prefixes_have_different_sources(): void
    {
        $appSource = new ArraySource();
        $appSource['name'] = 'MyApp';

        $envSource = new ArraySource();
        $envSource['name'] = 'production';

        $this->config->sources['app'] = [$appSource];
        $this->config->sources['env'] = [$envSource];

        $this->assertSame('MyApp', $this->config->getString('app/name'));
        $this->assertSame('production', $this->config->getString('env/name'));
    }

    public function test_multiple_sources_per_prefix_check_in_order(): void
    {
        $source1 = new ArraySource();
        $source1['priority'] = 'first';

        $source2 = new ArraySource();
        $source2['priority'] = 'second';
        $source2['only_in_second'] = 'value2';

        $this->config->sources['app'] = [$source1, $source2];

        $this->assertSame('first', $this->config->getString('app/priority'));
        $this->assertSame('value2', $this->config->getString('app/only_in_second'));
    }

    // ==================== getString() with defaults Tests ====================

    public function test_get_string_returns_default_when_key_not_found(): void
    {
        $result = $this->config->getString('app/nonexistent', 'default_value');
        $this->assertSame('default_value', $result);
    }

    public function test_get_string_returns_actual_value_over_default(): void
    {
        $source = new ArraySource();
        $source['key'] = 'actual_value';
        $this->config->sources['app'] = [$source];

        $result = $this->config->getString('app/key', 'default_value');
        $this->assertSame('actual_value', $result);
    }

    public function test_get_string_returns_default_when_prefix_not_found(): void
    {
        $result = $this->config->getString('nonexistent/key', 'default_value');
        $this->assertSame('default_value', $result);
    }

    // ==================== getInt() with defaults Tests ====================

    public function test_get_int_returns_default_when_key_not_found(): void
    {
        $result = $this->config->getInt('app/nonexistent', 42);
        $this->assertSame(42, $result);
    }

    public function test_get_int_returns_actual_value_over_default(): void
    {
        $source = new ArraySource();
        $source['key'] = 100;
        $this->config->sources['app'] = [$source];

        $result = $this->config->getInt('app/key', 42);
        $this->assertSame(100, $result);
    }

    // ==================== getFloat() with defaults Tests ====================

    public function test_get_float_returns_default_when_key_not_found(): void
    {
        $result = $this->config->getFloat('app/nonexistent', 3.14);
        $this->assertSame(3.14, $result);
    }

    public function test_get_float_returns_actual_value_over_default(): void
    {
        $source = new ArraySource();
        $source['key'] = 2.71;
        $this->config->sources['app'] = [$source];

        $result = $this->config->getFloat('app/key', 3.14);
        $this->assertSame(2.71, $result);
    }

    // ==================== getBool() with defaults Tests ====================

    public function test_get_bool_returns_default_when_key_not_found(): void
    {
        $result = $this->config->getBool('app/nonexistent', true);
        $this->assertTrue($result);

        $result = $this->config->getBool('app/nonexistent', false);
        $this->assertFalse($result);
    }

    public function test_get_bool_returns_actual_value_over_default(): void
    {
        $source = new ArraySource();
        $source['key'] = true;
        $this->config->sources['app'] = [$source];

        $result = $this->config->getBool('app/key', false);
        $this->assertTrue($result);
    }

    // ==================== getObject() with defaults Tests ====================

    public function test_get_object_returns_default_when_key_not_found(): void
    {
        $default = new \stdClass();
        $default->prop = 'value';

        $result = $this->config->getObject('app/nonexistent', \stdClass::class, $default);
        $this->assertSame($default, $result);
    }

    public function test_get_object_returns_actual_value_over_default(): void
    {
        $actual = new \stdClass();
        $actual->prop = 'actual';

        $default = new \stdClass();
        $default->prop = 'default';

        $source = new ArraySource();
        $source['key'] = $actual;
        $this->config->sources['app'] = [$source];

        $result = $this->config->getObject('app/key', \stdClass::class, $default);
        $this->assertSame($actual, $result);
    }

    // ==================== getRaw() with defaults Tests ====================

    public function test_get_raw_returns_default_when_key_not_found(): void
    {
        $result = $this->config->getRaw('app/nonexistent', 'default');
        $this->assertSame('default', $result);
    }

    public function test_get_raw_returns_actual_value_over_default(): void
    {
        $source = new ArraySource();
        $source['key'] = 'actual';
        $this->config->sources['app'] = [$source];

        $result = $this->config->getRaw('app/key', 'default');
        $this->assertSame('actual', $result);
    }

    public function test_get_raw_returns_default_of_various_types(): void
    {
        $this->assertSame(42, $this->config->getRaw('app/nonexistent', 42));
        $this->assertSame(['array'], $this->config->getRaw('app/nonexistent', ['array']));

        $obj = new \stdClass();
        $this->assertSame($obj, $this->config->getRaw('app/nonexistent', $obj));
    }

}
