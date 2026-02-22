<?php

declare(strict_types=1);

namespace AdvClientAPI\Services;

use AdvClientAPI\Core\Config;
use AdvClientAPI\Contracts\LoggerInterface;
use AdvClientAPI\Contracts\InsuranceServiceInterface;
use AdvClientAPI\Utilities\RetryPolicy;

/**
 * Abstract base service with common functionality
 */
abstract class BaseService implements InsuranceServiceInterface
{
    protected Config $config;
    protected LoggerInterface $logger;
    protected RetryPolicy $retryPolicy;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->logger = $config->getLogger();
        $this->retryPolicy = new RetryPolicy(
            $config->getMaxRetries(),
            $config->getBackoffFactor()
        );
    }

    /**
     * Make HTTP request with retry logic
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $url Full URL
     * @param array<string, string> $headers HTTP headers
     * @param string|null $body Request body
     * @return array{status_code: int, headers: array, body: string}
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     */
    protected function makeRequest(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null
    ): array {
        return $this->retryPolicy->execute(function () use ($method, $url, $headers, $body) {
            return $this->executeRequest($method, $url, $headers, $body);
        });
    }

    /**
     * Execute HTTP request via CURL
     *
     * @param string $method
     * @param string $url
     * @param array<string, string> $headers
     * @param string|null $body
     * @return array{status_code: int, headers: array, body: string}
     */
    protected function executeRequest(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null
    ): array {
        $ch = curl_init();

        try {
            $curlOptions = [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->config->getRequestTimeoutSec(),
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
            ];

            if (!empty($headers)) {
                $curlOptions[CURLOPT_HTTPHEADER] = array_map(
                    fn($k, $v) => "{$k}: {$v}",
                    array_keys($headers),
                    array_values($headers)
                );
            }

            if ($body !== null && $method !== 'GET') {
                $curlOptions[CURLOPT_POSTFIELDS] = $body;
            }

            curl_setopt_array($ch, $curlOptions);

            $responseBody = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            if ($responseBody === false) {
                throw new \AdvClientAPI\Exceptions\InsuranceApiException(
                    "CURL error: {$curlError}"
                );
            }

          
        } finally {
          
            return [
                'status_code' => (int)$statusCode,
                'headers' => [],
                'body' => (string)$responseBody,
            ];
        }
    }
}
