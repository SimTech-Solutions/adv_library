<?php

declare(strict_types=1);

namespace AdvClientAPI\Auth;

use AdvClientAPI\Contracts\TokenCacheInterface;

/**
 * In-memory token cache implementation
 * Simple array-based cache with expiration handling
 */
class InMemoryTokenCache implements TokenCacheInterface
{
    /** @var array<string, array{accessToken: string, expiresAt: int}> */
    private array $cache = [];

    /**
     * Get a cached token by key
     *
     * @param string $key
     * @return ?array
     */
    public function get(string $key): ?array
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        $cached = $this->cache[$key];

        // Check if expired
        if (time() > $cached['expiresAt']) {
            unset($this->cache[$key]);
            return null;
        }

        return $cached;
    }

    /**
     * Set a token in cache
     *
     * @param string $key
     * @param string $accessToken
     * @param int $expiresAt Unix timestamp
     * @return void
     */
    public function set(string $key, string $accessToken, int $expiresAt): void
    {
        $this->cache[$key] = [
            'accessToken' => $accessToken,
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * Delete a token from cache
     *
     * @param string $key
     * @return void
     */
    public function delete(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Clear all cached tokens
     *
     * @return void
     */
    public function clear(): void
    {
        $this->cache = [];
    }

    /**
     * Get cache size (for testing)
     *
     * @return int
     */
    public function size(): int
    {
        return count($this->cache);
    }

    /**
     * Get all cache keys (for testing)
     *
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->cache);
    }
}
