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
 * Configuration source that holds multiple other sources and checks them in order.
 */
class AggregatorSource implements ConfigSourceInterface
{

    /** @var ConfigSourceInterface[] $sources */
    protected array $sources;

    public function __construct(ConfigSourceInterface ...$sources)
    {
        $this->sources = $sources;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        foreach ($this->sources as $source) {
            if ($source->has($key))
                return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        foreach ($this->sources as $source) {
            if ($source->has($key))
                return $source->get($key);
        }
        return null;
    }

}
