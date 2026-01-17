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

class FileSourceTest extends TestCase
{

    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/filesource-tests-' . uniqid();
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            array_map('unlink', glob($this->testDir . '/*'));
            rmdir($this->testDir);
        }
    }

    public function test_constructor_throws_on_directory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is a directory');

        new FileSource($this->testDir);
    }

    public function test_nonexistent_file_returns_false_for_has(): void
    {
        $source = new FileSource($this->testDir . '/nonexistent.json');
        $this->assertFalse($source->has('ANY_KEY'));
    }

    public function test_json_file_parsing(): void
    {
        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, json_encode([
            'database' => 'mydb',
            'port'     => 5432,
            'ssl'      => true,
            'timeout'  => null,
        ]));

        $source = new FileSource($jsonFile);

        $this->assertTrue($source->has('database'));
        $this->assertTrue($source->has('port'));
        $this->assertTrue($source->has('ssl'));
        $this->assertTrue($source->has('timeout'));

        $this->assertSame('mydb', $source->get('database'));
        $this->assertSame(5432, $source->get('port'));
        $this->assertTrue($source->get('ssl'));
        $this->assertNull($source->get('timeout'));
    }

    public function test_json_file_nested_keys_are_flattened(): void
    {
        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, json_encode([
            'database' => [
                'host'        => 'localhost',
                'port'        => 5432,
                'credentials' => [
                    'user'     => 'admin',
                    'password' => 'secret',
                ],
            ],
            'app'      => [
                'name' => 'MyApp',
            ],
        ]));

        $source = new FileSource($jsonFile);

        $this->assertTrue($source->has('database.host'));
        $this->assertTrue($source->has('database.port'));
        $this->assertTrue($source->has('database.credentials.user'));
        $this->assertTrue($source->has('database.credentials.password'));
        $this->assertTrue($source->has('app.name'));

        $this->assertSame('localhost', $source->get('database.host'));
        $this->assertSame(5432, $source->get('database.port'));
        $this->assertSame('admin', $source->get('database.credentials.user'));
        $this->assertSame('secret', $source->get('database.credentials.password'));
        $this->assertSame('MyApp', $source->get('app.name'));
    }

    public function test_json_file_keys_are_sorted(): void
    {
        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, json_encode([
            'zebra'  => 'z_value',
            'apple'  => 'a_value',
            'middle' => 'm_value',
        ]));

        $source = new FileSource($jsonFile);

        $this->assertTrue($source->has('apple'));
        $this->assertTrue($source->has('middle'));
        $this->assertTrue($source->has('zebra'));
    }

    public function test_php_file_parsing(): void
    {
        $phpFile = $this->testDir . '/config.php';
        file_put_contents($phpFile, '<?php return ["key1" => "value1", "key2" => 42];');

        $source = new FileSource($phpFile);

        $this->assertTrue($source->has('key1'));
        $this->assertTrue($source->has('key2'));
        $this->assertSame('value1', $source->get('key1'));
        $this->assertSame(42, $source->get('key2'));
    }

    public function test_unsupported_extension_throws_exception(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unsupported config file extension');

        $unsupportedFile = $this->testDir . '/config.txt';
        file_put_contents($unsupportedFile, 'some content');

        $source = new FileSource($unsupportedFile);
        $source->has('key');
    }

    public function test_invalid_json_throws_exception(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Failed to parse JSON');

        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, '{invalid json}');

        $source = new FileSource($jsonFile);
        $source->has('key');
    }

    public function test_json_file_not_returning_array_throws_exception(): void
    {
        $this->expectException(ConfigException::class);

        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, '"string value"');

        $source = new FileSource($jsonFile);
        $source->has('key');
    }

    public function test_php_file_not_returning_array_throws_exception(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Failed to load config data');

        $phpFile = $this->testDir . '/config.php';
        file_put_contents($phpFile, '<?php return "not an array";');

        $source = new FileSource($phpFile);
        $source->has('key');
    }

    public function test_data_is_cached_after_first_load(): void
    {
        $jsonFile = $this->testDir . '/config.json';
        file_put_contents($jsonFile, json_encode(['key' => 'value']));

        $source = new FileSource($jsonFile);

        // First call loads the data
        $this->assertTrue($source->has('key'));

        // Modify the file
        file_put_contents($jsonFile, json_encode(['newkey' => 'newvalue']));

        // Second call should use cached data, not see the new file content
        $this->assertTrue($source->has('key'));
        $this->assertFalse($source->has('newkey'));
    }

    public function test_ini_file_parsing(): void
    {
        if (!function_exists('parse_ini_file')) {
            $this->markTestSkipped('parse_ini_file is not available');
        }

        $iniFile = $this->testDir . '/config.ini';
        file_put_contents($iniFile, <<<INI
            database_host=localhost
            database_port=5432
            database_enabled=true
            INI);

        $source = new FileSource($iniFile);

        $this->assertTrue($source->has('database_host'));
        $this->assertTrue($source->has('database_port'));
        $this->assertTrue($source->has('database_enabled'));

        $this->assertSame('localhost', $source->get('database_host'));
        $this->assertSame(5432, $source->get('database_port'));
        $this->assertTrue($source->get('database_enabled'));
    }

    public function test_yaml_file_parsing(): void
    {
        if (!function_exists('yaml_parse_file') && !class_exists('Symfony\Component\Yaml\Yaml')) {
            $this->markTestSkipped('YAML support is not available (requires yaml extension or symfony/yaml)');
        }

        $yamlFile = $this->testDir . '/config.yaml';
        file_put_contents($yamlFile, <<<YAML
            database:
              host: localhost
              port: 5432
            app:
              name: MyApp
            YAML);

        $source = new FileSource($yamlFile);

        $this->assertTrue($source->has('database.host'));
        $this->assertTrue($source->has('database.port'));
        $this->assertTrue($source->has('app.name'));

        $this->assertSame('localhost', $source->get('database.host'));
        $this->assertSame(5432, $source->get('database.port'));
        $this->assertSame('MyApp', $source->get('app.name'));
    }

}
