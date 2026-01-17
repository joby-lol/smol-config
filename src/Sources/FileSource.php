<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use Joby\Smol\Config\ConfigException;
use Joby\Smol\Config\ConfigSourceInterface;
use Symfony\Component\Yaml\Yaml;

class FileSource implements ConfigSourceInterface
{

    /**
     * Loaded data cache
     * @var array<string,mixed>|null
     */
    protected array|null $data = null;

    public function __construct(protected string $path)
    {
        if (is_file($path) && !is_readable($path))
            throw new \InvalidArgumentException("File '$path' is not readable.");
        if (is_dir($path))
            throw new \InvalidArgumentException("The provided config file path '$path' is a directory, not a file.");
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        $this->loadData();
        return $this->data[$key];
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $this->loadData();
        return array_key_exists($key, $this->data);
    }

    /**
     * Load data from the file if it hasn't been loaded yet.
     * @phpstan-assert !null $this->data
     */
    protected function loadData(): void
    {
        // if data is already loaded do nothing
        if ($this->data !== null)
            return;
        // if file doesn't exist, set data to empty array
        if (!is_file($this->path)) {
            $this->data = [];
            return;
        }
        // look for a parser for the file extension
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);
        $data = match (strtolower($extension)) {
            'json'  => $this->parseJsonFile($this->path),
            'ini'   => $this->parseIniFile($this->path),
            'php'   => include $this->path,
            'yaml'  => $this->parseYamlFile($this->path),
            'yml'   => $this->parseYamlFile($this->path),
            default => throw new ConfigException("Unsupported config file extension for file '{$this->path}'."),
        };
        if (!is_array($data))
            throw new ConfigException("Failed to load config data from file '{$this->path}'.");
        $this->data = $this->flattenKeyNames($data);
    }

    /**
     * Flattens multi-dimensional arrays into single-dimensional arrays with dot-separated key names.
     * 
     * @param array<mixed,mixed> $data
     * @param string $prefix used for recursion, should be left blank when calling initially
     * @return array<string,mixed>
     */
    protected function flattenKeyNames(array $data, string $prefix = ''): array
    {
        $flattened = [];
        foreach ($data as $key => $value) {
            $fullKey = $prefix === '' ? $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenKeyNames($value, $fullKey));
            }
            else {
                $flattened[$fullKey] = $value;
            }
        }
        ksort($flattened);
        return $flattened; // @phpstan-ignore-line because phpstan can't figure out the types here
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseYamlFile(string $path): array
    {
        // first try to use the yaml extension if it exists
        if (function_exists('yaml_parse_file')) {
            $data = yaml_parse_file($path);
            if (!is_array($data))
                throw new ConfigException("Failed to parse YAML config file '$path'.");
            return $data; // @phpstan-ignore-line because phpstan can't figure out the types here
        }
        // fall back to symfony/yaml if it's installed
        if (class_exists(Yaml::class)) {
            $content = file_get_contents($path);
            if ($content === false)
                throw new ConfigException("Failed to read YAML config file '$path'.");
            $data = Yaml::parse($content);
            if (!is_array($data))
                throw new ConfigException("Failed to parse YAML config file '$path'.");
            return $data; // @phpstan-ignore-line because phpstan can't figure out the types here
        }
        // if neither is available, throw an exception
        throw new ConfigException("YAML config file support requires the yaml PHP extension or the symfony/yaml package.");
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseJsonFile(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false)
            throw new ConfigException("Failed to read JSON config file '$path'.");
        $data = json_decode($content, true);
        if (!is_array($data))
            throw new ConfigException("Failed to parse JSON config file '$path'.");
        return $data; // @phpstan-ignore-line because phpstan can't figure out the types here
    }

    /**
     * @return array<string,mixed>
     */
    protected function parseIniFile(string $path): array
    {
        if (!function_exists('parse_ini_file'))
            throw new ConfigException("INI config file support requires the ini PHP extension.");
        $data = parse_ini_file($path, true, INI_SCANNER_TYPED);
        if ($data === false)
            throw new ConfigException("Failed to parse INI config file '$path'.");
        return $data; // @phpstan-ignore-line because phpstan can't figure out the types here
    }

}
