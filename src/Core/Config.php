<?php

declare(strict_types=1);

namespace AdvClientAPI\Core;

use AdvClientAPI\Contracts\LoggerInterface;
use AdvClientAPI\Contracts\TokenCacheInterface;
use AdvClientAPI\Auth\InMemoryTokenCache;
use AdvClientAPI\Utilities\NoopLogger;
// use AdvClientAPI\Exceptions\ConfigException;

/**
 * Configuration management for the insurance API client
 * Loads configuration from environment variables, array, or defaults
 */
 class Config
{
    // ADVANCECARE SOAP ENDPOINTS
    private string $advanceCareQualUrl;
    private string $advanceCareProdUrl;
    private string $advanceCareEnv;

    // ORACLE REST ENDPOINTS
    private string $oracleBaseUrl;
    private string $oracleTestUrl;
    private string $oracleProdUrl;
    private String $oracleScope;
    private String $testScope;
    private String $prodScope;
    private String $tokenUrl;


    // HTTP RETRY CONFIGURATION
    private int $maxRetries;
    private float $backoffFactor;
    private int $requestTimeoutSec;

    // TOKEN CACHE CONFIGURATION
    private int $tokenCacheTtlSec;
    private TokenCacheInterface $tokenCache;

    // LOGGING
    private LoggerInterface $logger;

    /**
     * Constructor with default values
     */
    public function __construct()
    {
        // ADVANCECARE SOAP defaults
        $this->advanceCareQualUrl = 'https://wsdev.advancecare.com/zonaReservadaWSAO/EligibilityWSAO/wsdl/EligibilityWSAO.wsdl';
        $this->advanceCareProdUrl = 'https://profissional.adv-angola.com/zonaReservadaWSAO/EligibilityWSAO/wsdl/EligibilityWSAO.wsdl';
        $this->advanceCareEnv = 'PROD';


        // ORACLE REST defaults
        $this->oracleTestUrl = 'https://fmxovjwlbbhe4wdambldgmyxju.apigateway.eu-frankfurt-1.oci.customer-oci.com/oig-test/exchanges/integration/';
        $this->oracleProdUrl = 'https://fmxovjwlbbhe4wdambldgmyxju.apigateway.eu-frankfurt-1.oci.customer-oci.com/oig-prod/exchanges/integration/';
        $this->oracleBaseUrl = $this->oracleProdUrl;
        $this->testScope ='https://adva-test-ohi.oracleindustry.com/test/urn::ohi-components-apis';
        $this->prodScope = 'https://adva-prod-ohi.oracleindustry.com/prod/urn::ohi-components-apis';
        $this->oracleScope = $this->prodScope;
        $this->tokenUrl = 'https://idcs-7d4260755fda42d1bd9606e8fc6ebd07.identity.oraclecloud.com/oauth2/v1/token';



        // HTTP/Retry defaults
        $this->maxRetries = 3;
        $this->backoffFactor = 2.0;
        $this->requestTimeoutSec = 60;

        // Token cache defaults
        $this->tokenCacheTtlSec = 3600;
        $this->tokenCache = new InMemoryTokenCache();
        
        // Logging
        $this->logger = new NoopLogger();
    }
    public static function testInstance(): self
    {
        $config = new self();
        $config->oracleBaseUrl = $config->oracleTestUrl;
        $config->advanceCareEnv = 'QUAL';
   
  
        $config->oracleScope = $config->testScope;
        return $config;
    }

    // /**
    //  * Create config from environment variables
    //  *
    // //  * @return self
    //  */
    // public static function fromEnv(): self
    // {
    //     $config = new self();

    //     // ADVANCECARE SOAP
    //     if ($value = getenv('ADVANCECARE_ENV')) {
    //         $config->setAdvanceCareEnv($value);
    //     }
    //     if ($value = getenv('ADVANCECARE_SOAP_URL')) {
    //         $config->setAdvanceCareUrl($value);
    //     }
    //     if ($value = getenv('ADVANCECARE_SOAP_URL_QUAL')) {
    //         $config->advanceCareQualUrl = $value;
    //     }
    //     if ($value = getenv('ADVANCECARE_SOAP_URL_PROD')) {
    //         $config->advanceCareProdUrl = $value;
    //     }

    //     // ORACLE REST
    //     if ($value = getenv('ORACLE_BASE_URL')) {
    //         $config->oracleBaseUrl = $value;
    //     }
    //     if ($value = getenv('ORACLE_BASE_URL_TEST')) {
    //         $config->oracleTestUrl = $value;
    //     }
    //     if ($value = getenv('ORACLE_BASE_URL_PROD')) {
    //         $config->oracleProdUrl = $value;
    //     }

    //     // HTTP CLIENT
    //     if ($value = getenv('HTTP_MAX_RETRIES')) {
    //         $config->maxRetries = (int)$value;
    //     }
    //     if ($value = getenv('HTTP_BACKOFF_FACTOR')) {
    //         $config->backoffFactor = (float)$value;
    //     }
    //     if ($value = getenv('HTTP_REQUEST_TIMEOUT_SEC')) {
    //         $config->requestTimeoutSec = (int)$value;
    //     }

    //     // TOKEN CACHE
    //     if ($value = getenv('TOKEN_CACHE_TTL_SEC')) {
    //         $config->tokenCacheTtlSec = (int)$value;
    //     }

    //     return $config;
    // }

    // /**
    //  * Create config from array
    //  *
    //  * @param array<string, mixed> $config
    //  * @return self
    //  */
    // public static function fromArray(array $config): self
    // {
    //     // $instance = self::fromEnv(); // Start with env defaults

    //     // Override with provided values
    //     if (isset($config['advancecare_env'])) {
    //         $instance->setAdvanceCareEnv($config['advancecare_env']);
    //     }
    //     if (isset($config['advancecare_soap_url'])) {
    //         $instance->setAdvanceCareUrl($config['advancecare_soap_url']);
    //     }
    //     if (isset($config['advancecare_soap_url_qual'])) {
    //         $instance->advanceCareQualUrl = $config['advancecare_soap_url_qual'];
    //     }
    //     if (isset($config['advancecare_soap_url_prod'])) {
    //         $instance->advanceCareProdUrl = $config['advancecare_soap_url_prod'];
    //     }

    //     if (isset($config['oracle_base_url'])) {
    //         $instance->oracleBaseUrl = $config['oracle_base_url'];
    //     }
    //     if (isset($config['oracle_base_url_test'])) {
    //         $instance->oracleTestUrl = $config['oracle_base_url_test'];
    //     }
    //     if (isset($config['oracle_base_url_prod'])) {
    //         $instance->oracleProdUrl = $config['oracle_base_url_prod'];
    //     }

    //     if (isset($config['http_max_retries'])) {
    //         $instance->maxRetries = (int)$config['http_max_retries'];
    //     }
    //     if (isset($config['http_backoff_factor'])) {
    //         $instance->backoffFactor = (float)$config['http_backoff_factor'];
    //     }
    //     if (isset($config['http_request_timeout_sec'])) {
    //         $instance->requestTimeoutSec = (int)$config['http_request_timeout_sec'];
    //     }

    //     if (isset($config['token_cache_ttl_sec'])) {
    //         $instance->tokenCacheTtlSec = (int)$config['token_cache_ttl_sec'];
    //     }
    //     if (isset($config['token_cache'])) {
    //         $instance->setTokenCache($config['token_cache']);
    //     }

    //     if (isset($config['logger'])) {
    //         $instance->setLogger($config['logger']);
    //     }

    //     return $instance;
    // }

    // /**
    //  * Set AdvanceCare environment (QUAL or PROD)
    //  *
    //  * @param string $env
    //  * @return void
    //  * @throws ConfigException
    //  */
    // public function setAdvanceCareEnv(string $env): void
    // {
    //     $env = strtoupper($env);
    //     if (!in_array($env, ['QUAL', 'PROD'], true)) {
    //         throw new ConfigException("Invalid AdvanceCare environment: {$env}");
    //     }
    //     $this->advanceCareEnv = $env;
    // }

    // /**
    //  * Set AdvanceCare SOAP URL (overrides env)
    //  *
    //  * @param string $url
    //  * @return void
    //  */
    // public function setAdvanceCareUrl(string $url): void
    // {
    //     $this->advanceCareQualUrl = $url;
    //     $this->advanceCareProdUrl = $url;
    // }

    // /**
    //  * Set Oracle base URL (overrides env)
    //  *
    //  * @param string $url
    //  * @return void
    //  */
    // public function setOracleBaseUrl(string $url): void
    // {
    //     $this->oracleBaseUrl = $url;
    // }

    // /**
    //  * Set token cache implementation
    //  *
    //  * @param TokenCacheInterface $cache
    //  * @return void
    //  */
    // public function setTokenCache(TokenCacheInterface $cache): void
    // {
    //     $this->tokenCache = $cache;
    // }

    // /**
    //  * Set logger implementation
    //  *
    //  * @param LoggerInterface $logger
    //  * @return void
    //  */
    // public function setLogger(LoggerInterface $logger): void
    // {
    //     $this->logger = $logger;
    // }

    // ==================== GETTERS ====================

    // /**
    //  * Get AdvanceCare SOAP URL based on environment
    //  *
    //  * @return string
    //  */
    public function getAdvanceCareUrl(): string
    {
        return $this->advanceCareEnv === 'PROD'
            ? $this->advanceCareProdUrl
            : $this->advanceCareQualUrl;
    }
    public function getOracleTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    // /**
    //  * Get current AdvanceCare environment
    //  *
    //  * @return string
    //  */
    // public function getAdvanceCareEnv(): string
    // {
    //     return $this->advanceCareEnv;
    // }

    /**
     * Get Oracle base URL
     *
     * @return string
     */
    public function getOracleBaseUrl(): string
    {
        return $this->oracleBaseUrl;
    }
    public function getOracleScope(): string
    {
        return $this->oracleScope;
    }

    /**
     * Get max retries
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Get backoff factor
     *
     * @return float
     */
    public function getBackoffFactor(): float
    {
        return $this->backoffFactor;
    }

    /**
     * Get request timeout in seconds
     *
     * @return int
     */
    public function getRequestTimeoutSec(): int
    {
        return $this->requestTimeoutSec;
    }

    /**
     * Get token cache TTL in seconds
     *
     * @return int
     */
    public function getTokenCacheTtlSec(): int
    {
        return $this->tokenCacheTtlSec;
    }

    /**
     * Get token cache instance
     *
     * @return TokenCacheInterface
     */
    public function getTokenCache(): TokenCacheInterface
    {
        return $this->tokenCache;
    }

    /**
     * Get logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get all configuration as array (for debugging)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'advancecare_env' => $this->advanceCareEnv,
            'advancecare_url' => $this->getAdvanceCareUrl(),
            'oracle_base_url' => $this->oracleBaseUrl,
            'max_retries' => $this->maxRetries,
            'backoff_factor' => $this->backoffFactor,
            'request_timeout_sec' => $this->requestTimeoutSec,
            'token_cache_ttl_sec' => $this->tokenCacheTtlSec,
        ];
    }
}
