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
 * Interface for a unified configuration management system. It's basically
 * a key/value store, with one additional method to interpolate values into strings by name. How the underlying implementation works is up to the implementer.
 */
interface ConfigInterface
{

    /**
     * Add a source for a specific key prefix. It will be checked in the order added, so any earlier source that has the key will take precedence.
     */
    public function addSource(string $prefix, ConfigSourceInterface $source): void;

    /**
     * Determine whether a config option exists.
     *
     * @param string|Stringable $key
     *
     * @return bool
     */
    public function has(string|Stringable $key): bool;

    /**
     * Get a raw value from config using its string key. Throws an exception if it doesn't exist, unless a non-null default is provided that can be returned instead.
     *
     * @throws ConfigException
     */
    public function getRaw(string|Stringable $key, mixed $default = null): mixed;

    /**
     * Get a string value from config using its string key. Throws an exception if it doesn't exist or isn't a string or type that can be safely cast to string.
     * 
     * String values will be automatically interpolated before being returned.
     * 
     * Throws an exception if the requested key is not found, unless a non-null default is provided that can be returned instead.
     */
    public function getString(string|Stringable $key, string|Stringable|null $default = null): string;

    /**
     * Get an integer value from config using its string key. Throws an exception if it doesn't exist or isn't an integer or type/string that can be safely cast to integer. "Safely" includes truncating floats or float-like values.
     * 
     * Throws an exception if the requested key is not found, unless a non-null default is provided that can be returned instead.
     */
    public function getInt(string|Stringable $key, int|null $default = null): int;

    /**
     * Get a float value from config using its string key. Throws an exception if it doesn't exist or isn't a float or type/string that can be safely cast to float.
     * 
     * Throws an exception if the requested key is not found, unless a non-null default is provided that can be returned instead.
     */
    public function getFloat(string|Stringable $key, float|null $default = null): float;

    /**
     * Get a boolean value from config using its string key. Throws an exception if it doesn't exist or isn't a boolean.
     * 
     * Throws an exception if the requested key is not found, unless a non-null default is provided that can be returned instead.
     */
    public function getBool(string|Stringable $key, bool|null $default = null): bool;

    /**
     * Get an object value from config using its string key. Throws an exception if it doesn't exist or isn't an object of the specified class.
     * 
     * Throws an exception if the requested key is not found, unless a non-null default is provided that can be returned instead.
     *
     * @template T of object
     * @param string|Stringable $key   The configuration key to retrieve.
     * @param class-string<T> $class The expected class name of the object.
     * @param T|null $default A default value to return if the key is not found.
     *
     * @return T The object associated with the specified key.
     * @throws ConfigException
     */
    public function getObject(string|Stringable $key, string $class, object|null $default = null): object;

    /**
     * Interpolates the given string value by processing any placeholders or variables within it.
     * 
     * Placeholders should be in the form `${config_key}`.
     * 
     * If any placeholders do not exist or are non-scalar and non-Stringable an exception will be thrown.
     *
     * @param string $value The input string containing placeholders or variables to be interpolated.
     *
     * @return string The resulting string after interpolation.
     * @throws ConfigException
     */
    public function interpolate(string $value): string;

}
