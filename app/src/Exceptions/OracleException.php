<?php

declare(strict_types=1);

namespace AdvClientAPI\Exceptions;

/**
 * Exception thrown when Oracle REST API requests fail
 */
class OracleException extends InsuranceApiException
{
    private int $statusCode = 0;
    private string $endpoint = '';
    private string $responseBody = '';

    public function __construct(
        string $message,
        int $statusCode = 0,
        string $endpoint = '',
        string $responseBody = ''
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->endpoint = $endpoint;
        $this->responseBody = self::maskSensitiveData($responseBody);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
