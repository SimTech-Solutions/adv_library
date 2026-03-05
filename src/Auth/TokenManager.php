<?php

declare(strict_types=1);

namespace AdvClientAPI\Auth;

use AdvClientAPI\Contracts\LoggerInterface;
use AdvClientAPI\Contracts\TokenCacheInterface;
use AdvClientAPI\Exceptions\AuthException;
use AdvClientAPI\Utilities\DateFormatter;


/**
 * Manages OAuth2 token lifecycle with caching
 * Automatically fetches new tokens when expired
 */
class TokenManager
{
    private TokenCacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        TokenCacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->logger = $logger;

        // print('Caching spot: '. $this->cache);
    }

    /**
     * Get access token, checking cache first
     * Automatically fetches new token if expired or missing
     *
     * @param string $tokenUrl OAuth2 token endpoint
     * @param string $clientId Client ID
     * @param string $clientSecret Client secret`
     * @param string $scope Token scope
     * @return string Access token
     * @throws AuthException
     */
    public function getToken(
        string $clientId,
        string $clientSecret,
        string $tokenUrl,

        string $scope
    ): string {
        // print($tokenUrl);
        // print($clientId);
        // print($clientSecret);
        // print($scope);
        // Generate cache key
        $cacheKey = $this->generateCacheKey($clientId, $scope);

        // Check cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug('Token found in cache', [
                'clientId' => $clientId,
                'scope' => $scope,
                'expiresIn' => DateFormatter::getSecondsUntilExpiration($cached['expiresAt']),
            ]);
            return $cached['accessToken'];
        }

        // $this->logger->info('Fetching new token', [
        //     'clientId' => $clientId,
        //     'tokenUrl' => $tokenUrl,
        // ]);

        // Fetch new token
        $token = $this->fetchNewToken($tokenUrl, $clientId, $clientSecret, $scope);

        // Cache the token
        $this->cache->set($cacheKey, $token['accessToken'], $token['expiresAt']);

        return $token['accessToken'];
    }

    /**
     * Invalidate cached token (force refresh on next request)
     *
     * @param string $clientId
     * @param string $scope
     * @return void
     */
    public function invalidateToken(string $clientId, string $scope): void
    {
        $cacheKey = $this->generateCacheKey($clientId, $scope);
        $this->cache->delete($cacheKey);
        $this->logger->debug('Token invalidated', [
            'clientId' => $clientId,
            'scope' => $scope,
        ]);
    }

    /**
     * Fetch new token from OAuth2 endpoint
     *
     * @param string $tokenUrl
     * @param string $clientId
     * @param string $clientSecret
     * @param string $scope
     * @return array{accessToken: string, expiresAt: int}
     * @throws AuthException
     */
    private function fetchNewToken(
        string $tokenUrl,
        string $clientId,
        string $clientSecret,
        string $scope
    ): array {
        $data = [
            'scope'=>$scope,
            'grant_type'=>'client_credentials'
        ];
        $ch = curl_init();

        // print("Token Url: $tokenUrl   ");
        // print("Client Id: $clientId   ");
        // print("Client Secret: $clientSecret   ");
        // print("Scope: $scope   ");
        // curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));


        curl_setopt_array($ch, [

            CURLOPT_URL => $tokenUrl,
            CURLOPT_POST => true,
      
            CURLOPT_POSTFIELDS => http_build_query(
                $data
            // [
            //     'grant_type' => 'client_credentials',
            //     'client_id' => $clientId,
            //     'client_secret' => $clientSecret,
            //     'scope' => $scope,
            // ]
            ),
            CURLOPT_HTTPHEADER => [
                'authorization: Basic '.base64_encode($clientId . ':' . $clientSecret),
                'Content-type: application/x-www-form-urlencoded'

            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            // CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            // CURLOPT_USERPWD => "{$clientId}:{$clientSecret}",
            // CURLOPT_HEADER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            throw new AuthException(
                "CURL error: {$curlError}",
                $tokenUrl,
                $clientId
            );
        }

        if ($httpCode !== 200) {
            throw new AuthException(
                "Token endpoint returned HTTP {$httpCode}: {$response}",
                $tokenUrl,
                $clientId
            );
        }
        $parts = explode("\r\n\r\n", $response, 2); 
        $body = $parts[1] ?? $response;
      
      
        // Parse response


        $data = json_decode($body, true);
      
    //    var_dump($data);

        if (!is_array($data) || !isset($data['access_token'])) {
            throw new AuthException(
                "Invalid token response: missing access_token",
                $tokenUrl,
                $clientId
            );
        }

        // Parse expiration time
        $expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 3600;
        $expiresAt = time() + $expiresIn;

        return [
            'accessToken' => $data['access_token'],
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * Generate cache key from clientId and scope
     *
     * @param string $clientId
     * @param string $scope
     * @return string
     */
    private function generateCacheKey(string $clientId, string $scope): string
    {
        return md5("{$clientId}:{$scope}");
    }
}
