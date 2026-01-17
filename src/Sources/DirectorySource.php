<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use Joby\Smol\Config\ConfigException;

/**
 * Configuration source that reads data from any number of files in a directory. Precedence is determined by filename alphabetical order.
 */
class DirectorySource extends AggregatorSource
{

    public function __construct(string $path)
    {
        // validate that it isn't a file
        if (is_file($path) && !is_dir($path))
            throw new ConfigException("The provided config directory path '$path' is a file, not a directory.");
        // if directory doesn't exist, this will just be an empty source
        if (!is_dir($path)) {
            parent::__construct();
            return;
        }
        // glob files in the directory
        $files = glob(rtrim($path, '/\\') . '/*.{json,yaml,yml,ini,php}', GLOB_BRACE);
        if ($files === false)
            throw new ConfigException("Failed to read config directory '$path'.");
        // loop through files and create FileSource for each
        $sources = [];
        foreach ($files as $file) {
            if (is_dir($file))
                continue; // skip directories
            $sources[] = new FileSource($file);
        }
        parent::__construct(...$sources);
    }

}
