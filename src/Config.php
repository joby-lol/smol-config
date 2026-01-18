<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config;

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
    public function getRaw(string $key, mixed $default = null): mixed
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
    public function has(string $key): bool
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
            return $this->interpolate(
                $this->castToString($this->getRaw($key)),
                [...$previous, $key],
            );
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
    public function getBool(string $key, bool|null $default = null): bool
    {
        $value = $this->getRaw($key, $default);
        if (is_string($value) || $value instanceof Stringable)
            $value = $this->interpolate((string) $value);
        return $this->castToBool($value);
    }

    protected function castToBool(mixed $value): bool
    {
        if (is_bool($value))
            return $value;
        if (is_int($value))
            return match ($value) {
                1       => true,
                0       => false,
                default => throw new ConfigTypeException("Value $value cannot be safely cast to boolean."),
            };
        if (is_float($value))
            return match ($value) {
                1.0     => true,
                0.0     => false,
                default => throw new ConfigTypeException("Value $value cannot be safely cast to boolean."),
            };
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['1', 'true', 'yes', 'on'], true))
                return true;
            if (in_array($lower, ['0', 'false', 'no', 'off'], true))
                return false;
        }
        throw new ConfigTypeException("Value of type " . gettype($value) . " cannot be safely cast to boolean.");
    }

    /**
     * @inheritDoc
     */
    public function getFloat(string $key, float|null $default = null): float
    {
        $value = $this->getRaw($key, $default);
        if (is_string($value) || $value instanceof Stringable)
            $value = $this->interpolate((string) $value);
        return $this->castToFloat($value);
    }

    protected function castToFloat(mixed $value): float
    {
        if (is_float($value))
            return $value;
        if (is_int($value))
            return (float) $value;
        if (is_string($value)) {
            if (is_numeric($value))
                return (float) $value;
        }
        throw new ConfigTypeException("Value of type " . gettype($value) . " cannot be safely cast to float.");
    }

    /**
     * @inheritDoc
     */
    public function getInt(string $key, int|null $default = null): int
    {
        $value = $this->getRaw($key, $default);
        if (is_string($value) || $value instanceof Stringable)
            $value = $this->interpolate((string) $value);
        return $this->castToInt($value);
    }

    protected function castToInt(mixed $value): int
    {
        if (is_int($value))
            return $value;
        if (is_float($value))
            return (int) $value;
        if (is_string($value)) {
            if (ctype_digit($value))
                return (int) $value;
            if (is_numeric($value))
                return (int) $value;
        }
        throw new ConfigTypeException("Value of type " . gettype($value) . " cannot be safely cast to integer.");
    }

    /**
     * @inheritDoc
     */
    public function getObject(string $key, string $class, object|null $default = null): object
    {
        $value = $this->getRaw($key, $default);
        if (!is_object($value) || !is_a($value, $class))
            throw new ConfigTypeException("Config key '$key' is not an object of class '$class'. Got " . (is_object($value) ? get_class($value) : gettype($value)) . " instead.");
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getString(string $key, string|null $default = null): string
    {
        $value = $this->getRaw($key, $default);
        if (is_string($value) || $value instanceof Stringable)
            return $this->interpolate((string) $value);
        return $this->castToString($value);
    }

    protected function castToString(mixed $value): string
    {
        if (is_string($value))
            return $value;
        if (is_int($value) || is_float($value))
            return (string) $value;
        if (is_bool($value))
            return $value ? 'true' : 'false';
        throw new ConfigTypeException("Value of type " . gettype($value) . " cannot be safely cast to string.");
    }

}
