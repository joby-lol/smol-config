<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use ArrayAccess;
use Joby\Smol\Config\ConfigSourceInterface;

/**
 * Array-based configuration source. Implements array access for easy manipulation, you can use it like a normal array for getting and setting values.
 * 
 * @implements ArrayAccess<string,mixed>
 */
class ArraySource implements ConfigSourceInterface, ArrayAccess
{

    /**
     * Internal data
     * @var array<string,mixed>
     */
    protected array $data = [];

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        return $this->data[$key];
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists((string) $offset, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[(string) $offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[(string) $offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[(string) $offset]);
    }

}
