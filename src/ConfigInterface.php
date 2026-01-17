<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config;

/**
 * Interface for a unified configuration management system. It's basically
 * a key/value store, with one additional method to interpolate values into strings by name. How the underlying implementation works is up to the implementer.
 */
interface ConfigInterface
{

    /**
     * Determine whether a config option exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get a raw value from config using its string key. Throws an exception if it doesn't exist.
     *
     * @throws ConfigException
     */
    public function getRaw(string $key): mixed;

    /**
     * Get a string value from config using its string key. Throws an exception if it doesn't exist or isn't a string or type that can be safely cast to string.
     * 
     * String values will be automatically interpolated before being returned.
     */
    public function getString(string $key): string;

    /**
     * Get an integer value from config using its string key. Throws an exception if it doesn't exist or isn't an integer or type/string that can be safely cast to integer. "Safely" includes truncating floats or float-like values.
     */
    public function getInt(string $key): int;

    /**
     * Get a float value from config using its string key. Throws an exception if it doesn't exist or isn't a float or type/string that can be safely cast to float.
     */
    public function getFloat(string $key): float;

    /**
     * Get a boolean value from config using its string key. Throws an exception if it doesn't exist or isn't a boolean.
     */
    public function getBool(string $key): bool;

    /**
     * Get an object value from config using its string key. Throws an exception if it doesn't exist or isn't an object of the specified class.
     *
     * @template T of object
     * @param string $key   The configuration key to retrieve.
     * @param class-string<T> $class The expected class name of the object.
     *
     * @return T The object associated with the specified key.
     * @throws ConfigException
     */
    public function getObject(string $key, string $class): object;

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
