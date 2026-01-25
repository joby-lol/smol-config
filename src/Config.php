<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config;

use Joby\Smol\Cast\Cast;
use Joby\Smol\Cast\TypeCastException;
use Stringable;

/**
 * Config implementation that supports loading values with certain prefixes from various sources, such as files, directories of files, or even SQLite databases.
 */
class Config implements ConfigInterface
{

    /**
     * Array of key prefixes and sources that may provide values from within them. For example setting under 'env' may map keys starting with 'env/' to values from a FileConfigSource that loads from a .env file.
     * @var array<string,ConfigSourceInterface[]>
     */
    public array $sources = [];

    /**
     * Add a source for a specific key prefix. It will be checked in the order added, so any earlier source that has the key will take precedence.
     */
    public function addSource(string $prefix, ConfigSourceInterface $source): void
    {
        if (!isset($this->sources[$prefix])) {
            $this->sources[$prefix] = [];
        }
        $this->sources[$prefix][] = $source;
    }

    /**
     * @inheritDoc
     */
    public function getRaw(string|Stringable $key, mixed $default = null): mixed
    {
        list($prefix, $key) = $this->splitKeyPrefix($key);
        if (!isset($this->sources[$prefix])) {
            if ($default !== null)
                return $default;
            else
                throw new ConfigKeyNotFoundException("Config key '$key' not found in any source for prefix '$prefix'.");
        }
        foreach ($this->sources[$prefix] as $source) {
            if ($source->has($key)) {
                return $source->get($key);
            }
        }
        if ($default !== null)
            return $default;
        else
            throw new ConfigKeyNotFoundException("Config key '$key' not found in any source for prefix '$prefix'.");
    }

    /**
     * @inheritDoc
     */
    public function has(string|Stringable $key): bool
    {
        list($prefix, $key) = $this->splitKeyPrefix($key);
        if (!isset($this->sources[$prefix])) {
            return false;
        }
        foreach ($this->sources[$prefix] as $source) {
            if ($source->has($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     * 
     * @param array<int,string> $previous Used internally to track previous keys during interpolation to detect circular references
     */
    public function interpolate(string $value, array $previous = []): string
    {
        return preg_replace_callback('/\${([^}]+)}/', function ($matches) use ($previous): string {
            $key = $matches[1];
            if (in_array($key, $previous))
                throw new ConfigException("Circular reference detected during interpolation of config key '$key': " . implode(' -> ', $previous) . " -> $key.");
            try {
                return $this->interpolate(
                    Cast::string($this->getRaw($key)) ?? '',
                    [...$previous, $key],
                );
            }
            catch (TypeCastException $e) {
                throw new ConfigTypeException("Error casting during interpolation of config key '$key': " . $e->getMessage(), previous: $e);
            }
        }, $value)
            ?? throw new ConfigException("Error during interpolation of value '$value'.");
    }

    /**
     * Splits a key into its prefix and the remaining key part. Throws an exception if no prefix is found.
     * 
     * @return array{0: string, 1: string}
     */
    protected function splitKeyPrefix(string $key): array
    {
        $parts = explode('/', $key, 2);
        if (count($parts) === 2)
            return [$parts[0], $parts[1]];
        else
            throw new ConfigKeyNotFoundException("Config key '$key' does not include a prefix");
    }

    /**
     * @inheritDoc
     */
    public function getObject(string|Stringable $key, string $class, object|null $default = null): object
    {
        $value = $this->getRaw($key, $default);
        if (!is_object($value) || !is_a($value, $class))
            throw new ConfigTypeException("Config key '$key' is not an object of class '$class'. Got " . (is_object($value) ? get_class($value) : gettype($value)) . " instead.");
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function getCastableValue(string $name): mixed
    {
        if (!$this->has($name))
            return null;
        $value = $this->getRaw($name);
        if (is_string($value) || $value instanceof Stringable)
            $value = $this->interpolate((string) $value);
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getBool(string|Stringable $key, bool|null $default = null): bool
    {
        $value = $this->getCastableValue((string) $key) ?? $default;
        if ($value === null)
            throw new ConfigKeyNotFoundException("Config key '$key' not found.");
        try {
            return Cast::bool($value);
        }
        catch (TypeCastException $e) {
            throw new ConfigTypeException("Config key '$key' cannot be cast to bool.", previous: $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getFloat(string|Stringable $key, float|null $default = null): float
    {
        $value = $this->getCastableValue((string) $key) ?? $default;
        if ($value === null)
            throw new ConfigKeyNotFoundException("Config key '$key' not found.");
        try {
            return Cast::float($value);
        }
        catch (TypeCastException $e) {
            throw new ConfigTypeException("Config key '$key' cannot be cast to float.", previous: $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getInt(string|Stringable $key, int|null $default = null): int
    {
        $value = $this->getCastableValue((string) $key) ?? $default;
        if ($value === null)
            throw new ConfigKeyNotFoundException("Config key '$key' not found.");
        try {
            return Cast::int($value);
        }
        catch (TypeCastException $e) {
            throw new ConfigTypeException("Config key '$key' cannot be cast to int.", previous: $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getString(string|Stringable $key, string|Stringable|null $default = null): string
    {
        $value = $this->getCastableValue((string) $key) ?? $default;
        if ($value === null)
            throw new ConfigKeyNotFoundException("Config key '$key' not found.");
        try {
            return Cast::string($value);
        }
        catch (TypeCastException $e) {
            throw new ConfigTypeException("Config key '$key' cannot be cast to string.", previous: $e);
        }
    }

}
