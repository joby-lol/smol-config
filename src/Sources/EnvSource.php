<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use Joby\Smol\Config\ConfigSourceInterface;

/**
 * Configuration source that reads data from the $_ENV superglobal.
 */
class EnvSource implements ConfigSourceInterface
{

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_ENV);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        return $_ENV[$key];
    }

}
