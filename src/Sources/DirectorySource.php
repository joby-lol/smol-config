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
        // glob files in the directory (no longer using glob because of GLOB_BRACE issues on some systems)
        $files = scandir($path);
        if ($files === false)
            throw new ConfigException("Failed to read config directory '$path'.");
        // loop through files and create FileSource for each
        $sources = [];
        $supported_extensions = ['json', 'yaml', 'yml', 'ini', 'php'];
        foreach ($files as $file) {
            $file = $path . '/' . $file;
            if (is_dir($file))
                continue; // skip directories
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($extension, $supported_extensions, true))
                continue; // skip unsupported file types
            $sources[] = new FileSource($file);
        }
        parent::__construct(...$sources);
    }

}
