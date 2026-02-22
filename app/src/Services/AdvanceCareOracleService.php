<?php

declare(strict_types=1);

namespace AdvClientAPI\Services;

use AdvClientAPI\Core\Config;
use AdvClientAPI\Auth\TokenManager;
use AdvClientAPI\Exceptions\OracleException;
use AdvClientAPI\Mappers\OracleResponseMapper;
// use AdvClientAPI\Utilities\DateFormatter;

/**
 * Oracle REST API service implementation
 * Handles OAuth2 token management with automatic caching
 */
class AdvanceCareOracleService extends BaseService
{
    private TokenManager $tokenManager;

    // Oracle endpoints
    private const ENDPOINT_SUBMIT = '{baseUrl}/provider/{providerId}/submitReservation';
    private const ENDPOINT_CANCEL = '{baseUrl}/provider/{providerId}/cancelReservation';

    public function __construct(Config $config)
    {
        parent::__construct($config);
        $this->tokenManager = new TokenManager(
            $config->getTokenCache(),
            $config->getLogger()
        );
    }

    /**
     * Perform pharma act via Oracle REST API
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws OracleException
     */
    public function performPharmaAct(array $data): array
    {
        return $this->executeDataRequest($data, 'submitReservation');
    }

    /**
     * Create eligibility via Oracle REST API
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws OracleException
     */
    public function createEligibility(array $data): array
    {
        return $this->executeDataRequest($data, 'submitReservation');
    }

    /**
     * Add invoice via Oracle REST API
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws OracleException
     */
    public function addInvoice(array $data): array
    {
        return $this->executeDataRequest($data, 'submitReservation');
    }

    /**
     * Cancel eligibility via Oracle REST API
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws OracleException
     */
    public function cancelEligibility(array $data): array
    {
        return $this->executeDataRequest($data, 'cancelReservation');
    }

    /**
     * Execute REST request with OAuth2 token management
     *
     * @param array<string, mixed> $data
     * @param string $operation
     * @return array<string, mixed>
     * @throws OracleException
     */
    private function executeDataRequest(array $data, string $operation): array
    {
        $this->validateRequestData($data);

        // Extract auth credentials
        $auth = $data['auth'] ?? [];
        $requestData = $data['requestData'] ?? $data;

        // Get OAuth2 token with automatic caching
        $token = $this->getToken($auth);

        // Build endpoint URL
        $providerId = $auth['providerId'] ?? 'default';
        $endpointTemplate = $operation === 'cancelReservation'
            ? self::ENDPOINT_CANCEL
            : self::ENDPOINT_SUBMIT;

        $endpoint = str_replace(
            ['{baseUrl}', '{providerId}'],
            [$this->config->getOracleBaseUrl(), $providerId],
            $endpointTemplate
        );

        // Prepare request
        $headers = [
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $body = json_encode($requestData);

        $this->logger->debug('Sending Oracle REST request', [
            'operation' => $operation,
            'endpoint' => $endpoint,
        ]);

        try {
            $response = $this->makeRequest('POST', $endpoint, $headers, $body);

            // Handle 303 redirect
            if ($response['status_code'] === 303) {
                // Response should have a Location header
                $this->logger->debug('Oracle returned 303 redirect');
                // In real implementation, would follow redirect
            }

            if ($response['status_code'] < 200 || $response['status_code'] >= 300) {
                throw new OracleException(
                    "Oracle API request failed with HTTP {$response['status_code']}",
                    $response['status_code'],
                    $endpoint,
                    $response['body']
                );
            }

            // Parse response
            $mapper = new OracleResponseMapper($response['body']);
            return $mapper->map();
        } catch (OracleException $e) {
            $this->logger->error('Oracle REST request failed', [
                'operation' => $operation,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            throw new OracleException(
                "Oracle REST request error: {$e->getMessage()}",
                0,
                $endpoint,
                ''
            );
        }
    }

    /**
     * Get OAuth2 token with automatic caching
     *
     * @param array<string, mixed> $auth
     * @return string Access token
     * @throws OracleException
     */
    private function getToken(array $auth): string
    {
        $required = ['clientId', 'clientSecret', 'scope'];
        foreach ($required as $field) {
            if (!isset($auth[$field])) {
                throw new OracleException(
                    "Missing required auth field: {$field}"
                );
            }
        }

        // Default token URL if not provided
        $tokenUrl = $auth['tokenUrl'] ?? $this->config->getOracleBaseUrl() . '/oauth2/token';

        try {
            return $this->tokenManager->getToken(
                $tokenUrl,
                $auth['clientId'],
                $auth['clientSecret'],
                $auth['scope']
            );
        } catch (\Exception $e) {
            throw new OracleException(
                "Failed to obtain token: {$e->getMessage()}"
            );
        }
    }

    /**
     * Validate request data
     *
     * @param array<string, mixed> $data
     * @throws \InvalidArgumentException
     */
    private function validateRequestData(array $data): void
    {
        if (!isset($data['auth']) || !is_array($data['auth'])) {
            throw new \InvalidArgumentException('Missing "auth" configuration in request data');
        }

        if (!isset($data['requestData']) && !isset($data['customer_code'])) {
            throw new \InvalidArgumentException('Missing request data fields');
        }
    }
}
