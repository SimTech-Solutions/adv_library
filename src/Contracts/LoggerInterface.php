<?php

declare(strict_types=1);

namespace AdvClientAPI\Contracts;

/**
 * Interface for logging
 * Compatible with PSR-3 LoggerInterface
 */
interface LoggerInterface
{
    /**
     * Log a debug message
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log an info message
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log a warning message
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log an error message
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function error(string $message, array $context = []): void;
}
