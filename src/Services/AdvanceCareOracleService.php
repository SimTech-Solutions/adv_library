<?php

declare(strict_types=1);

namespace AdvClientAPI\Services;

use Exception;

use AdvClientAPI\Core\Config;
use AdvClientAPI\Auth\TokenManager;
use AdvClientAPI\Exceptions\OracleException;
use AdvClientAPI\Mappers\OracleResponseMapper;


/**
 * Oracle REST API service implementation
 * Handles OAuth2 token management with automatic caching
 */
class AdvanceCareOracleService extends BaseService
{
    private TokenManager $tokenManager;

    // Oracle endpoints
    // private $config;
    public function __construct(Config $config)

    {

        parent::__construct($config);
        $this->tokenManager = new TokenManager(
            $config->getTokenCache(),
            $config->getLogger()
        );
        $this->config = $config;
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
        // Extract auth credentials
        $auth = $data['auth'] ?? [];
        if (empty($auth)) {
            throw (new OracleException(
                message: 'No Authentication details provided',
                responseBody: $auth

            ));
        }

        $requestData = $data['requestData'];

        if (empty($requestData)) {
            throw new OracleException(message: 'No request data provided');
        }

        // Get OAuth2 token with automatic caching
        try {
            $token = $this->getToken($auth);
        } catch (OracleException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new OracleException("Failed to get OAuth2 token: " . $e->getMessage());
        }

        // Build endpoint URL
        $providerId = $auth['providerId'];

        $endpoint = $this->config->getOracleBaseUrl() . $providerId . '/' . $operation;
       
        // Prepare request - add Bearer prefix 
        $headers = [
            'Authorization' => "Bearer $token",  // Add "Bearer " prefix
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Provider' => $providerId,
            "ohi-exchange-allowed-time-ms" => "60000"
        ];


        $body = json_encode($requestData);


        try {
            // print($endpoint);

            $response = $this->makeRequest("POST", $endpoint, $headers, $body);
  
         
          if ($response['status_code'] === 302 || $response['status_code'] === 303) {
                $responseHeaders = $response['headers'];
                $redirectUrl = null;
                
                // Check for Location header (Guzzle returns headers as associative array)
                if (isset($responseHeaders['Location']) && is_array($responseHeaders['Location'])) {
                    $redirectUrl = $responseHeaders['Location'][0];
                } elseif (isset($responseHeaders['location']) && is_array($responseHeaders['location'])) {
                    $redirectUrl = $responseHeaders['location'][0];
                }
                
                // checking for redirect Url
                if ($redirectUrl === null || empty($redirectUrl)) {
                    throw new OracleException(
                        'Redirect received but no Location header found',
                        $response['status_code'],
                        $endpoint,
                        
                    );
                }
                // Follow redirect with GET and KEEP the Authorization header 
                $redirectResponse = $this->makeRequest("GET", $redirectUrl, $headers);
         
                // var_dump($redirectResponse);
                if ($redirectResponse['status_code'] !== 200) {
                    // print('Redirected Request failed...');
                    throw new OracleException(
                        'Redirect request failed with status code ' . $redirectResponse['status_code'],
                        $redirectResponse['status_code'],
                        $redirectUrl,
                        (string)$redirectResponse['status_code']
                    );
                }

                $responseBody = $redirectResponse['body'];
                $mapper = new OracleResponseMapper(
                    $responseBody
                );
                $parsedData = $mapper->map();
          
                return $parsedData;
            }

            // Handle non-redirect responses
            if ($response['status_code'] !== 200) {
                throw new OracleException(
                    "Oracle API request failed with status code: " . $response['status_code'],
                    $response['status_code'],
                    $endpoint,
                    $response['body']
                );
            }

            // Parse successful response
            $responseBody = (string)$response['body'];
            $mapper = new OracleResponseMapper($responseBody);
            return $mapper->map();
        } catch (OracleException $e) {
            $this->logger->error('Oracle REST request failed ' . $e->getMessage(), [
                'operation' => $operation,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Exception $e) {

            throw new OracleException(
                "Oracle REST request error: " . $e->getMessage(),
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
    private  function  getToken(array $auth): string
    {
        $required = ['clientId', 'clientSecret'];
        foreach ($required as $field) {

            if (!isset($auth[$field])) {

                throw new OracleException(
                    "Missing required auth field: {$field}"
                );
            }
        }

        try {
            // Use scope from auth array if provided, otherwise use config default
            $scope = $auth['scope'] ?? $this->config->getOracleScope();

            $result = $this->tokenManager->getToken(
                $auth['clientId'],

                $auth['clientSecret'],
                $this->config->getOracleTokenUrl(),

                $scope
            );

            return $result;
        } catch (Exception $e) {
            throw new OracleException(
                "Failed to obtain token: " . $e->getMessage()
            );
        }
    }

    // /**
    //  * Validate request data
    //  *
    //  * @param array<string, mixed> $data
    //  * @throws \InvalidArgumentException
    //  */
    // private function validateRequestData(array $data): void
    // {
    //     if (!isset($data['auth']) || !is_array($data['auth'])) {
    //         throw new \InvalidArgumentException('Missing "auth" configuration in request data');
    //     }

    //     if (!isset($data['requestData']) && !isset($data['customer_code'])) {
    //         throw new \InvalidArgumentException('Missing request data fields');
    //     }
    // }
}
