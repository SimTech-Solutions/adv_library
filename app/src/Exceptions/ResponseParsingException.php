<?php

declare(strict_types=1);

namespace AdvClientAPI\Exceptions;

/**
 * Exception thrown when response parsing fails
 */
class ResponseParsingException extends InsuranceApiException
{
    private string $rawResponse = '';
    private string $expectedFormat = '';

    public function __construct(
        string $message,
        string $rawResponse = '',
        string $expectedFormat = ''
    ) {
        parent::__construct($message);
        $this->rawResponse = self::maskSensitiveData($rawResponse);
        $this->expectedFormat = $expectedFormat;
    }

    public function getRawResponse(): string
    {
        return $this->rawResponse;
    }

    public function getExpectedFormat(): string
    {
        return $this->expectedFormat;
    }
}
