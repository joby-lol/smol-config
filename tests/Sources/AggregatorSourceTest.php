<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use PHPUnit\Framework\TestCase;

class AggregatorSourceTest extends TestCase
{

    public function test_has_returns_false_when_no_sources_or_key_not_found(): void
    {
        $aggregator = new AggregatorSource();
        $this->assertFalse($aggregator->has('ANY_KEY'));

        $source1 = new ArraySource();
        $source1['KEY1'] = 'value1';

        $aggregator = new AggregatorSource($source1);
        $this->assertFalse($aggregator->has('NONEXISTENT_KEY'));
    }

    public function test_has_returns_true_when_key_found_in_first_source(): void
    {
        $source1 = new ArraySource();
        $source1['KEY1'] = 'value1';

        $source2 = new ArraySource();
        $source2['KEY2'] = 'value2';

        $aggregator = new AggregatorSource($source1, $source2);

        $this->assertTrue($aggregator->has('KEY1'));
        $this->assertTrue($aggregator->has('KEY2'));
    }

    public function test_has_checks_sources_in_order(): void
    {
        $source1 = new ArraySource();
        $source1['SHARED_KEY'] = 'from_source1';

        $source2 = new ArraySource();
        $source2['SHARED_KEY'] = 'from_source2';

        $aggregator = new AggregatorSource($source1, $source2);

        $this->assertTrue($aggregator->has('SHARED_KEY'));
    }

    public function test_get_returns_null_when_no_sources_or_key_not_found(): void
    {
        $aggregator = new AggregatorSource();
        $this->assertNull($aggregator->get('ANY_KEY'));

        $source1 = new ArraySource();
        $source1['KEY1'] = 'value1';

        $aggregator = new AggregatorSource($source1);
        $this->assertNull($aggregator->get('NONEXISTENT_KEY'));
    }

    public function test_get_returns_value_from_first_source_that_has_key(): void
    {
        $source1 = new ArraySource();
        $source1['KEY1'] = 'value1';
        $source1['SHARED'] = 'from_source1';

        $source2 = new ArraySource();
        $source2['KEY2'] = 'value2';
        $source2['SHARED'] = 'from_source2';

        $aggregator = new AggregatorSource($source1, $source2);

        $this->assertSame('value1', $aggregator->get('KEY1'));
        $this->assertSame('value2', $aggregator->get('KEY2'));
        // First source should win for shared keys
        $this->assertSame('from_source1', $aggregator->get('SHARED'));
    }

    public function test_get_returns_different_value_types(): void
    {
        $source = new ArraySource();
        $source['STRING_KEY'] = 'string_value';
        $source['INT_KEY'] = 42;
        $source['BOOL_KEY'] = false;
        $source['NULL_KEY'] = null;
        $source['ARRAY_KEY'] = ['nested' => 'array'];

        $aggregator = new AggregatorSource($source);

        $this->assertSame('string_value', $aggregator->get('STRING_KEY'));
        $this->assertSame(42, $aggregator->get('INT_KEY'));
        $this->assertFalse($aggregator->get('BOOL_KEY'));
        $this->assertNull($aggregator->get('NULL_KEY'));
        $this->assertSame(['nested' => 'array'], $aggregator->get('ARRAY_KEY'));
    }

    public function test_aggregator_with_multiple_sources_priority(): void
    {
        $source1 = new ArraySource();
        $source1['PRIORITY'] = 'first';
        $source1['ONLY_IN_1'] = 'value1';

        $source2 = new ArraySource();
        $source2['PRIORITY'] = 'second';
        $source2['ONLY_IN_2'] = 'value2';

        $source3 = new ArraySource();
        $source3['ONLY_IN_3'] = 'value3';
        $source3['PRIORITY'] = 'third';

        $aggregator = new AggregatorSource($source1, $source2, $source3);

        // All keys should be found
        $this->assertTrue($aggregator->has('ONLY_IN_1'));
        $this->assertTrue($aggregator->has('ONLY_IN_2'));
        $this->assertTrue($aggregator->has('ONLY_IN_3'));
        $this->assertTrue($aggregator->has('PRIORITY'));

        // Values should come from the appropriate source
        $this->assertSame('value1', $aggregator->get('ONLY_IN_1'));
        $this->assertSame('value2', $aggregator->get('ONLY_IN_2'));
        $this->assertSame('value3', $aggregator->get('ONLY_IN_3'));
        // First source should win for priority key
        $this->assertSame('first', $aggregator->get('PRIORITY'));
    }

    public function test_aggregator_with_no_sources(): void
    {
        $aggregator = new AggregatorSource();

        $this->assertFalse($aggregator->has('ANY_KEY'));
        $this->assertNull($aggregator->get('ANY_KEY'));
    }

    public function test_aggregator_with_single_source(): void
    {
        $source = new ArraySource();
        $source['KEY1'] = 'value1';
        $source['KEY2'] = 'value2';

        $aggregator = new AggregatorSource($source);

        $this->assertTrue($aggregator->has('KEY1'));
        $this->assertTrue($aggregator->has('KEY2'));
        $this->assertFalse($aggregator->has('NONEXISTENT'));

        $this->assertSame('value1', $aggregator->get('KEY1'));
        $this->assertSame('value2', $aggregator->get('KEY2'));
        $this->assertNull($aggregator->get('NONEXISTENT'));
    }

    public function test_aggregator_sources_can_be_different_types(): void
    {
        $arraySource = new ArraySource();
        $arraySource['ARRAY_KEY'] = 'array_value';

        $this->expectNotToPerformAssertions();

        // This test just verifies the aggregator accepts any ConfigSourceInterface
        $aggregator = new AggregatorSource($arraySource);
        $aggregator->has('ARRAY_KEY');
    }

}
