<?php

declare(strict_types=1);

namespace AdvClientAPI\Services;

use AdvClientAPI\Core\Config;
use AdvClientAPI\Contracts\LoggerInterface;
use AdvClientAPI\Contracts\InsuranceServiceInterface;
use AdvClientAPI\Utilities\RetryPolicy;
use AdvClientAPI\Exceptions\InsuranceApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Abstract base service with common functionality
 */
abstract class BaseService implements InsuranceServiceInterface
{
    protected Config $config;
    protected LoggerInterface $logger;
    protected RetryPolicy $retryPolicy;
    protected Client $httpClient;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->logger = $config->getLogger();
        
        // Initialize Guzzle HTTP client
        $this->httpClient = new Client([
            'verify' => false,
            'timeout' => 60,
        ]);

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

    return $this->retryPolicy->execute(
        fn() => $this->executeRequest($method, $url, $headers, $body)
    );
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
        try {
            $options = [
                'headers' => $headers,
                'allow_redirects' => false, // IMPORTANTE: Nós vamos controlar o redirect
            ];

            if ($body !== null && $method !== 'GET') {
                $options['body'] = $body;
            }

            $response = $this->httpClient->request($method, $url, $options);

            return [
                'status_code' => $response->getStatusCode(),
                'body'        => $response->getBody()->getContents(),
                'headers'     => $response->getHeaders(),
            ];

        } catch (GuzzleException $e) {
            $this->logger->error("Erro na requisição HTTP no executeRequest: " . $e->getMessage(), [
                'method' => $method,
                'url' => $url,
                'headers' => $headers,
                'body' => $body,
            ]);
            throw new InsuranceApiException("Erro interno ao processar pedido à seguradora.");
        } catch (\Exception $e) {
            $this->logger->error("Erro inesperado no executeRequest: " . $e->getMessage(), [
                'method' => $method,
                'url' => $url,
                'headers' => $headers,
                'body' => $body,
            ]);
            throw new InsuranceApiException("Erro interno ao processar pedido à seguradora.");
        }
    }
    // protected function executeRequest(
    //     string $method,
    //     string $url,
    //     array $headers = [],
    //     ?string $body = null
    // ): array {
    //     $ch = curl_init();

    //     try {
    //         $curlOptions = [
    //             CURLOPT_URL => $url,
    //             CURLOPT_CUSTOMREQUEST => $method,
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_TIMEOUT => $this->config->getRequestTimeoutSec(),
    //             CURLOPT_HEADER => true,
    //             // CURLOPT_FOLLOWLOCATION => true,
            
    //             // CURLOPT_MAXREDIRS => 5,
    //             // This bitmask tells cURL to resend POST data on 301, 302, and 303 redirects
    //             // CURLOPT_POSTREDIR => 1 | 2 | 4,
              
    //         ];

    //         if (!empty($headers)) {
    //             $curlOptions[CURLOPT_HTTPHEADER] = array_map(
    //                 fn($k, $v) => "{$k}: {$v}",
    //                 array_keys($headers),
    //                 array_values($headers)
    //             );
    //         }

    //         if ($body !== null && $method !== 'GET') {
    //             $curlOptions[CURLOPT_POSTFIELDS] = $body;
    //         }

    //         curl_setopt_array($ch, $curlOptions);
            
    //         $raw_response = curl_exec($ch);
    //         $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //         $curlError = curl_error($ch);
           
    //         if ($raw_response === false) {
    //             throw new InsuranceApiException(
    //                 "CURL error: {$curlError}"
    //             );
    //         }
    //         $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //         $responseHeader = substr($raw_response, 0, $headerSize);
    //         $headerLines = array_map('trim', explode("\r\n", $responseHeader));
    //         $responseBody = substr($raw_response, $headerSize );
    //         // var_dump($responseHeader);
    //         // var_dump($headerLines);


    //         return [ 
    //         'status_code' => (int)$statusCode,
    //         'headers' => $headerLines,
    //         'body' => (string)$responseBody,
    //     ];
    //     } finally {
    //       unset($ch);
    //     }
        
        
    // }
}
