<?php

declare(strict_types=1);

namespace AdvClientAPI\Utilities;

use AdvClientAPI\Contracts\LoggerInterface;

/**
 * No-op logger implementation (silent)
 */
class NoopLogger implements LoggerInterface
{
    public function debug(string $message, array $context = []): void
    {
        // Silent
    }

    public function info(string $message, array $context = []): void
    {
        // Silent
    }

    public function warning(string $message, array $context = []): void
    {
        // Silent
    }

    public function error(string $message, array $context = []): void
    {
        // Silent
    }
}
