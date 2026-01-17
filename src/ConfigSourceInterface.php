<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config;

/**
 * Interface for configuration sources that provide configuration values to the main Config class. They do not need to be aware of their own namespace. The main class will pass them a key with the appropriate prefix stripped, and will automatically add the prefix back when returning values.
 */
interface ConfigSourceInterface
{

    /**
     * Determine whether a config option exists in this source.
     */
    public function has(string $key): bool;

    /**
     * Get a value from this source using its string key. Should return null if the key does not exist.
     */
    public function get(string $key): mixed;

}
