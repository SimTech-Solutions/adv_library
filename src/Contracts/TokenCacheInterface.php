<?php

declare(strict_types=1);

namespace AdvClientAPI\Contracts;

/**
 * Interface for token caching
 */
interface TokenCacheInterface
{
    /**
     * Get a cached token by key
     *
     * @param string $key
     * @return ?array Format: ['accessToken' => string, 'expiresAt' => int]
     */
    public function get(string $key): ?array;

    /**
     * Set a token in cache
     *
     * @param string $key
     * @param string $accessToken
     * @param int $expiresAt Unix timestamp
     * @return void
     */
    public function set(string $key, string $accessToken, int $expiresAt): void;

    /**
     * Delete a token from cache
     *
     * @param string $key
     * @return void
     */
    public function delete(string $key): void;

    /**
     * Clear all cached tokens
     *
     * @return void
     */
    public function clear(): void;
}
