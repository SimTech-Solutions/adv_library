<?php

declare(strict_types=1);

namespace AdvClientAPI\Utilities;

/**
 * Retry policy with exponential backoff
 */
class RetryPolicy
{
    private int $maxRetries;
    private float $backoffFactor;
    private int $initialDelayMs;

    /**
     * Constructor
     *
     * @param int $maxRetries Maximum number of retry attempts
     * @param float $backoffFactor Exponential backoff multiplier
     * @param int $initialDelayMs Initial delay in milliseconds
     */
    public function __construct(
        int $maxRetries = 3,
        float $backoffFactor = 2.0,
        int $initialDelayMs = 100
    ) {
        $this->maxRetries = $maxRetries;
        $this->backoffFactor = $backoffFactor;
        $this->initialDelayMs = $initialDelayMs;
    }

    /**
     * Execute operation with automatic retries and exponential backoff
     *
     * @param callable $operation
     * @param array<class-string> $retryableExceptions Exception classes to retry on
     * @return mixed
     * @throws \Exception
     */
    public function execute(
        callable $operation,
        array $retryableExceptions = []
    ): mixed {
        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $operation();
            } catch (\Exception $e) {
                $lastException = $e;

                // If no retryable exceptions specified, retry on all exceptions
                if (empty($retryableExceptions)) {
                    if ($attempt < $this->maxRetries) {
                        $this->backoff($attempt);
                        continue;
                    }
                } else {
                    // Only retry if exception matches retryable types
                    $shouldRetry = false;
                    foreach ($retryableExceptions as $exceptionClass) {
                        if ($e instanceof $exceptionClass) {
                            $shouldRetry = true;
                            break;
                        }
                    }

                    if ($shouldRetry && $attempt < $this->maxRetries) {
                        $this->backoff($attempt);
                        continue;
                    }
                }

                throw $e;
            }
        }

        throw $lastException ?? new \Exception('Unknown error');
    }

    /**
     * Apply exponential backoff delay
     *
     * @param int $attempt Zero-based attempt number
     * @return void
     */
    private function backoff(int $attempt): void
    {
        $delayMs = (int)($this->initialDelayMs * pow($this->backoffFactor, $attempt));
        usleep($delayMs * 1000); // Convert milliseconds to microseconds
    }

    /**
     * Get configuration
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [
            'maxRetries' => $this->maxRetries,
            'backoffFactor' => $this->backoffFactor,
            'initialDelayMs' => $this->initialDelayMs,
        ];
    }
}
