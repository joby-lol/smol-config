# smolConfig

A lightweight PHP configuration management library with namespaced sources, automatic type conversion, and string interpolation.

## Installation

```bash
composer require joby-lol/smol-config
```

## About

smolConfig provides a unified interface for accessing configuration from multiple sources (files, directories, environment variables, arrays) with type-safe getters and automatic string interpolation.

**Key features:**

- **Namespaced sources**: Organize config by prefix (e.g., `env/`, `db/`, `app/`)
- **Multiple file formats**: JSON, YAML, INI, PHP
- **Type-safe access**: Dedicated getters with automatic type conversion
- **String interpolation**: Reference other config values with `${prefix/key}` syntax
- **Hierarchical flattening**: Nested structures converted to dot-notation keys
- **Source precedence**: Multiple sources per namespace checked in order

## Basic Usage

```php
use Joby\Smol\Config\Config;
use Joby\Smol\Config\Sources\{FileSource, EnvSource};

$config = new Config();

// Add sources under namespaces
$config->addSource('app', new FileSource('config/app.json'));
$config->addSource('env', new EnvSource());

// Type-safe access with automatic conversion
$debug = $config->getBool('app/debug');
$port = $config->getInt('app/port');
$name = $config->getString('app/name');

// String interpolation
$dsn = $config->getString('app/database_url');
// "postgresql://${env/DB_HOST}:5432/${env/DB_NAME}"
// Becomes: "postgresql://localhost:5432/myapp"
```

## Configuration Sources

### FileSource

Load configuration from JSON, YAML, INI, or PHP files. Nested structures are automatically flattened using dot notation.

```php
use Joby\Smol\Config\Sources\FileSource;

// JSON file
$config->addSource('app', new FileSource('config/app.json'));

// YAML file (requires yaml extension or symfony/yaml)
$config->addSource('settings', new FileSource('config/settings.yaml'));

// INI file
$config->addSource('legacy', new FileSource('config/legacy.ini'));

// PHP file (returns array)
$config->addSource('dynamic', new FileSource('config/dynamic.php'));
```

**File format examples:**

```json
// app.json
{
  "database": {
    "host": "localhost",
    "port": 5432
  },
  "debug": true
}
```

Access nested values with dot notation:
```php
$host = $config->getString('app/database.host'); // "localhost"
$port = $config->getInt('app/database.port');    // 5432
$debug = $config->getBool('app/debug');          // true
```

### DirectorySource

Load all configuration files from a directory. Files are processed in alphabetical order.

```php
use Joby\Smol\Config\Sources\DirectorySource;

// Loads all .json, .yaml, .yml, .ini, and .php files
$config->addSource('app', new DirectorySource('config/app.d'));

// Directory structure:
// config/app.d/
// ├── 01-database.json
// ├── 02-cache.yaml
// └── 03-features.php
```

Later files take precedence over earlier files for duplicate keys.

### ArraySource

In-memory configuration source with array access support.

```php
use Joby\Smol\Config\Sources\ArraySource;

$array = new ArraySource();
$array['database.host'] = 'localhost';
$array['database.port'] = 5432;
$array['debug'] = true;

$config->addSource('app', $array);

$host = $config->getString('app/database.host'); // "localhost"
```

### EnvSource

Access environment variables from `$_ENV`.

```php
use Joby\Smol\Config\Sources\EnvSource;

$config->addSource('env', new EnvSource());

$path = $config->getString('env/PATH');
$home = $config->getString('env/HOME');
```

### ServerSource

Access server variables from `$_SERVER`.

```php
use Joby\Smol\Config\Sources\ServerSource;

$config->addSource('server', new ServerSource());

$host = $config->getString('server/HTTP_HOST');
$method = $config->getString('server/REQUEST_METHOD');
```

### AggregatorSource

Combine multiple sources with precedence order.

```php
use Joby\Smol\Config\Sources\{AggregatorSource, FileSource, ArraySource};

// Create override pattern: defaults < environment-specific < local overrides
$aggregator = new AggregatorSource(
    new FileSource('config/local.json')          // Local overrides win
    new FileSource('config/production.json'),    // Then environment
    new FileSource('config/defaults.json'),      // Then defaults
);

$config->addSource('app', $aggregator);
```

## Type-Safe Getters

All getters automatically interpolate strings and perform type conversion.

### getString()

Returns a string value with interpolation applied.

```php
// Direct string
$name = $config->getString('app/name'); // "MyApp"

// With interpolation
$dsn = $config->getString('app/dsn');
// "${env/DB_DRIVER}://${env/DB_HOST}/${env/DB_NAME}"
// Becomes: "postgresql://localhost/myapp"

// Numbers and booleans converted to strings
$port = $config->getString('app/port'); // "5432" (from int 5432)
```

### getInt()

Returns an integer with automatic conversion from numeric strings and floats.

```php
$port = $config->getInt('app/port'); // 5432

// From string
$timeout = $config->getInt('app/timeout'); // 30 (from "30")

// From float (truncates decimal)
$limit = $config->getInt('app/limit'); // 100 (from 100.5)
```

**Note**: Float to int conversion truncates the decimal portion.

### getFloat()

Returns a float with automatic conversion from numeric strings and integers.

```php
$version = $config->getFloat('app/version'); // 1.5

// From string
$ratio = $config->getFloat('app/ratio'); // 0.75 (from "0.75")

// From int
$timeout = $config->getFloat('app/timeout'); // 30.0 (from 30)
```

### getBool()

Returns a boolean with permissive string parsing.

```php
$debug = $config->getBool('app/debug');

// Accepts these string values (case-insensitive):
// true:  "1", "true", "yes", "on"
// false: "0", "false", "no", "off"

// Also accepts int/float 1/0 and 1.0/0.0
```

### getObject()

Returns an object of a specific class (no type conversion or interpolation).

```php
use MyApp\DatabaseConfig;

$dbConfig = $config->getObject('app/database', DatabaseConfig::class);
```

### getRaw()

Returns the raw value without interpolation or type conversion.

```php
// Get uninterpolated string
$template = $config->getRaw('app/dsn');
// "${env/DB_DRIVER}://${env/DB_HOST}/${env/DB_NAME}"

// Get original type
$value = $config->getRaw('app/mixed'); // Could be array, object, etc.
```

## String Interpolation

Reference other configuration values using `${prefix/key}` syntax. Interpolation is recursive and includes circular reference detection.

### Basic Interpolation

```php
// config/app.json
{
  "name": "MyApp",
  "greeting": "Welcome to ${app/name}!"
}

$greeting = $config->getString('app/greeting');
// "Welcome to MyApp!"
```

### Cross-Namespace Interpolation

```php
// Environment variables
$_ENV['DB_HOST'] = 'db.example.com';
$_ENV['DB_NAME'] = 'production';

// config/app.json
{
  "database_url": "postgresql://${env/DB_HOST}/${env/DB_NAME}"
}

$url = $config->getString('app/database_url');
// "postgresql://db.example.com/production"
```

### Recursive Interpolation

```php
// config/app.json
{
  "base_url": "${env/SITE_PROTOCOL}://${env/SITE_HOST}",
  "api_url": "${app/base_url}/api",
  "docs_url": "${app/base_url}/docs"
}

$api = $config->getString('app/api_url');
// "https://example.com/api"
```

### Circular Reference Detection

```php
// config/app.json (invalid)
{
  "a": "${app/b}",
  "b": "${app/a}"
}

// Throws ConfigException with path:
// "Circular reference detected: app/a -> app/b -> app/a"
```

## Multiple Sources Per Namespace

Register multiple sources under the same namespace for fallback behavior. Sources are checked in the order added.

```php
// Add sources in order - first added is checked first
$config->addSource('app', new FileSource('config/local.json'));    // Checked first
$config->addSource('app', new FileSource('config/defaults.json')); // Fallback

// First source with the key wins
$value = $config->getString('app/some_key');
```

## Checking for Keys

```php
// Check if key exists in any source for the namespace
if ($config->has('app/optional_feature')) {
    $feature = $config->getString('app/optional_feature');
}
```

## Usage Patterns

### Environment-Based Configuration

```php
$config = new Config();

// Always load defaults
$config->addSource('app', new FileSource('config/defaults.json'));

// Load environment-specific overrides
$env = getenv('APP_ENV') ?: 'production';
$envFile = "config/{$env}.json";
if (file_exists($envFile)) {
    $config->addSource('app', new FileSource($envFile));
}

// Load local overrides (git-ignored)
if (file_exists('config/local.json')) {
    $config->addSource('app', new FileSource('config/local.json'));
}

// Access environment variables
$config->addSource('env', new EnvSource());
```

### Database Configuration

```php
// config/database.json
{
  "driver": "postgresql",
  "host": "${env/DB_HOST}",
  "port": 5432,
  "database": "${env/DB_NAME}",
  "username": "${env/DB_USER}",
  "password": "${env/DB_PASS}",
  "dsn": "${db/driver}://${db/host}:${db/port}/${db/database}"
}

$config->addSource('db', new FileSource('config/database.json'));
$config->addSource('env', new EnvSource());

// Use with PDO
$dsn = $config->getString('db/dsn');
$username = $config->getString('db/username');
$password = $config->getString('db/password');

$pdo = new PDO($dsn, $username, $password);
```

### Feature Flags

```php
// config/features.json
{
  "new_ui": true,
  "beta_features": false,
  "api_v2": true
}

$config->addSource('features', new FileSource('config/features.json'));

if ($config->getBool('features/new_ui')) {
    // Show new UI
}
```

### Multi-Tenant Configuration

```php
$config = new Config();

// Shared configuration
$config->addSource('app', new FileSource('config/app.json'));

// Tenant-specific overrides
$tenantId = getCurrentTenantId();
$config->addSource('tenant', new FileSource("config/tenants/{$tenantId}.json"));

// Tenant can override app settings
$name = $config->getString('tenant/name');
$theme = $config->getString('tenant/theme');
```

### Runtime Configuration

```php
// Start with file-based config
$config = new Config();
$config->addSource('app', new FileSource('config/app.json'));

// Override with runtime values
$runtime = new ArraySource();
$runtime['mode'] = 'maintenance';
$runtime['maintenance.message'] = 'System upgrade in progress';

// Runtime overrides take precedence
$config->addSource('app', $runtime);

$mode = $config->getString('app/mode'); // "maintenance"
```

### Configuration Directory Pattern

```php
// Load all configs from directory structure:
// config/
// ├── app.d/
// │   ├── 10-database.json
// │   ├── 20-cache.json
// │   └── 30-mail.json
// └── local.d/
//     └── overrides.json

$config = new Config();
$config->addSource('app', new DirectorySource('config/app.d'));
$config->addSource('app', new DirectorySource('config/local.d')); // Local overrides
```

### Combining Multiple Source Types

```php
$config = new Config();

// Application config from multiple sources
$config->addSource('app', new FileSource('config/app.json'));      // Base config
$config->addSource('app', new DirectorySource('config/app.d'));    // Modular configs
$config->addSource('app', new ArraySource());                      // Runtime overrides

// Environment variables
$config->addSource('env', new EnvSource());

// Server variables for request info
$config->addSource('server', new ServerSource());

// Access seamlessly across all sources
$appName = $config->getString('app/name');
$dbHost = $config->getString('env/DB_HOST');
$requestUri = $config->getString('server/REQUEST_URI');
```

## Requirements

Fully tested on PHP 8.3+, static analysis for PHP 8.1+.

## License

MIT License - See [LICENSE](LICENSE) file for details.