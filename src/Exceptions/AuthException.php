<?php

declare(strict_types=1);

namespace AdvClientAPI\Exceptions;

/**
 * Exception thrown when token generation/authentication fails
 */
class AuthException extends InsuranceApiException
{
    private string $tokenUrl = '';
    private string $clientId = '';

    public function __construct(
        string $message,
        string $tokenUrl = '',
        string $clientId = ''
    ) {
        parent::__construct($message);
        $this->tokenUrl = $tokenUrl;
        $this->clientId = $clientId;
    }

    public function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }
}
