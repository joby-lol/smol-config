<?php

/**
 * smolConfig
 * https://github.com/joby-lol/smol-config
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Config\Sources;

use Joby\Smol\Config\ConfigException;
use PHPUnit\Framework\TestCase;

class DirectorySourceTest extends TestCase
{

    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/directorysource-tests-' . uniqid();
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            array_map('unlink', glob($this->testDir . '/*'));
            rmdir($this->testDir);
        }
    }

    public function test_constructor_throws_on_file_path(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('is a file, not a directory');

        $filePath = $this->testDir . '/file.json';
        file_put_contents($filePath, '{}');

        new DirectorySource($filePath);
    }

    public function test_nonexistent_directory_is_treated_as_empty_source(): void
    {
        $nonexistentDir = $this->testDir . '/nonexistent';
        $source = new DirectorySource($nonexistentDir);

        $this->assertFalse($source->has('ANY_KEY'));
        $this->assertNull($source->get('ANY_KEY'));
    }

    public function test_empty_directory_has_no_keys(): void
    {
        $source = new DirectorySource($this->testDir);

        $this->assertFalse($source->has('ANY_KEY'));
        $this->assertNull($source->get('ANY_KEY'));
    }

    public function test_single_json_file_in_directory(): void
    {
        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, json_encode([
            'database_host' => 'localhost',
            'database_port' => 5432,
        ]));

        $source = new DirectorySource($this->testDir);

        $this->assertTrue($source->has('database_host'));
        $this->assertTrue($source->has('database_port'));
        $this->assertSame('localhost', $source->get('database_host'));
        $this->assertSame(5432, $source->get('database_port'));
    }

    public function test_multiple_files_in_directory_are_loaded_in_alphabetical_order(): void
    {
        // Create files in non-alphabetical order
        $fileB = $this->testDir . '/b_config.json';
        file_put_contents($fileB, json_encode(['PRIORITY' => 'from_b']));

        $fileA = $this->testDir . '/a_config.json';
        file_put_contents($fileA, json_encode(['PRIORITY' => 'from_a', 'ONLY_IN_A' => 'value_a']));

        $fileC = $this->testDir . '/c_config.json';
        file_put_contents($fileC, json_encode(['ONLY_IN_C' => 'value_c']));

        $source = new DirectorySource($this->testDir);

        // All keys should be accessible
        $this->assertTrue($source->has('PRIORITY'));
        $this->assertTrue($source->has('ONLY_IN_A'));
        $this->assertTrue($source->has('ONLY_IN_C'));

        // First file alphabetically should win for shared keys
        $this->assertSame('from_a', $source->get('PRIORITY'));
        $this->assertSame('value_a', $source->get('ONLY_IN_A'));
        $this->assertSame('value_c', $source->get('ONLY_IN_C'));
    }

    public function test_different_file_formats_are_supported(): void
    {
        $jsonFile = $this->testDir . '/a_config.json';
        file_put_contents($jsonFile, json_encode(['FROM_JSON' => 'json_value']));

        $phpFile = $this->testDir . '/b_config.php';
        file_put_contents($phpFile, '<?php return ["FROM_PHP" => "php_value"];');

        $source = new DirectorySource($this->testDir);

        $this->assertTrue($source->has('FROM_JSON'));
        $this->assertTrue($source->has('FROM_PHP'));
        $this->assertSame('json_value', $source->get('FROM_JSON'));
        $this->assertSame('php_value', $source->get('FROM_PHP'));
    }

    public function test_unsupported_file_extensions_are_ignored(): void
    {
        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, json_encode(['KEY' => 'value']));

        // Create files with unsupported extensions
        file_put_contents($this->testDir . '/ignored.txt', 'content');
        file_put_contents($this->testDir . '/ignored.xml', '<config></config>');

        // Should not throw exception
        $source = new DirectorySource($this->testDir);

        // JSON file should still be loaded
        $this->assertTrue($source->has('KEY'));
        $this->assertSame('value', $source->get('KEY'));
    }

    public function test_nested_keys_from_files_are_flattened(): void
    {
        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, json_encode([
            'app'      => [
                'name'    => 'MyApp',
                'version' => '1.0.0',
            ],
            'database' => [
                'host' => 'localhost',
            ],
        ]));

        $source = new DirectorySource($this->testDir);

        $this->assertTrue($source->has('app.name'));
        $this->assertTrue($source->has('app.version'));
        $this->assertTrue($source->has('database.host'));

        $this->assertSame('MyApp', $source->get('app.name'));
        $this->assertSame('1.0.0', $source->get('app.version'));
        $this->assertSame('localhost', $source->get('database.host'));
    }

    public function test_directory_with_subdirectories_ignores_them(): void
    {
        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, json_encode(['KEY' => 'value']));

        $subdir = $this->testDir . '/subdir';
        mkdir($subdir);
        file_put_contents($subdir . '/ignored.json', json_encode(['IGNORED' => 'should_not_load']));

        $source = new DirectorySource($this->testDir);

        $this->assertTrue($source->has('KEY'));
        $this->assertFalse($source->has('IGNORED'));
    }

    public function test_yml_extension_is_supported(): void
    {
        if (!function_exists('yaml_parse_file') && !class_exists('Symfony\Component\Yaml\Yaml')) {
            $this->markTestSkipped('YAML support is not available');
        }

        $ymlFile = $this->testDir . '/config.yml';
        file_put_contents($ymlFile, <<<YAML
            app_name: MyApp
            app_version: 1.0.0
            YAML);

        $source = new DirectorySource($this->testDir);

        $this->assertTrue($source->has('app_name'));
        $this->assertTrue($source->has('app_version'));
        $this->assertSame('MyApp', $source->get('app_name'));
        $this->assertSame('1.0.0', $source->get('app_version'));
    }

    public function test_yaml_extension_is_supported(): void
    {
        if (!function_exists('yaml_parse_file') && !class_exists('Symfony\Component\Yaml\Yaml')) {
            $this->markTestSkipped('YAML support is not available');
        }

        $yamlFile = $this->testDir . '/config.yaml';
        file_put_contents($yamlFile, <<<YAML
            setting1: value1
            setting2: value2
            YAML);

        $source = new DirectorySource($this->testDir);

        $this->assertTrue($source->has('setting1'));
        $this->assertTrue($source->has('setting2'));
        $this->assertSame('value1', $source->get('setting1'));
        $this->assertSame('value2', $source->get('setting2'));
    }

    public function test_ini_extension_is_supported(): void
    {
        if (!function_exists('parse_ini_file')) {
            $this->markTestSkipped('parse_ini_file is not available');
        }

        $iniFile = $this->testDir . '/config.ini';
        file_put_contents($iniFile, <<<INI
            setting1=value1
            setting2=value2
            INI);

        $source = new DirectorySource($this->testDir);

        $this->assertTrue($source->has('setting1'));
        $this->assertTrue($source->has('setting2'));
        $this->assertSame('value1', $source->get('setting1'));
        $this->assertSame('value2', $source->get('setting2'));
    }

}
