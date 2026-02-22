# PHP Insurance API Library

A comprehensive PHP library that encapsulates all insurance API processing logic including SOAP and REST API communication, OAuth2 token management with caching, response parsing, and retry logic with exponential backoff.

## Prerequisites

- Docker
- Docker Compose

## Getting Started

### 1. Build and Start the Docker Container

```bash
docker-compose up --build
```

This will:
- Build the PHP 8.2 container
- Install all composer dependencies
- Set up the development environment

### 2. Access the Container

```bash
docker-compose exec php bash
```

### 3. Run Tests

```bash
docker-compose exec php composer test
```

### 4. Code Quality Checks

```bash
# Run PHPStan static analysis
docker-compose exec php composer phpstan

# Run PHP Code Sniffer
docker-compose exec php composer phpcs

# Fix code style issues
docker-compose exec php composer phpcs-fix
```

## Project Structure

```
insurance-api-php-library/
├── src/
│   ├── Core/                    # Main client and configuration
│   ├── Services/                # SOAP and REST service implementations
│   ├── Auth/                    # Token management and caching
│   ├── Mappers/                 # Response mapping
│   ├── Templates/               # SOAP templates
│   ├── Exceptions/              # Custom exceptions
│   ├── Utilities/               # Helper classes
│   └── Contracts/               # Interfaces
├── tests/                       # Unit and integration tests
├── Dockerfile                   # PHP container definition
├── docker-compose.yml           # Docker composition
└── composer.json                # PHP dependencies
```

## Configuration

Environment variables are set in `docker-compose.yml`:

- `ADVANCECARE_ENV`: Environment (QUAL or PROD)
- `HTTP_MAX_RETRIES`: Maximum retry attempts
- `HTTP_BACKOFF_FACTOR`: Exponential backoff multiplier
- `HTTP_REQUEST_TIMEOUT_SEC`: Request timeout in seconds
- `TOKEN_CACHE_TTL_SEC`: Token cache TTL in seconds
- `LOG_LEVEL`: Logging level

## Development

All development happens inside the Docker container. The current directory is mounted as a volume, so changes made locally are reflected immediately in the container.

### Using the Library

After building, you can use the library in your PHP code:

```php
<?php
use AdvClientAPI\Core\InsuranceApiClient;
use AdvClientAPI\Core\Config;

// Create client with configuration
$config = Config::fromEnv();
$client = new InsuranceApiClient($config);

// Call API methods
$result = $client->performPharmaAct([
    'username' => 'user@domain',
    'password' => 'secret',
    'customer_code' => 'CUST123',
    // ... other required fields
]);

echo $result['success'] ? 'Success!' : 'Error: ' . $result['error'];
?>
```

## Stopping the Container

```bash
docker-compose down
```

## More Information

See [PHP_LIBRARY_PLAN.md](PHP_LIBRARY_PLAN.md) for the complete design and implementation plan.
