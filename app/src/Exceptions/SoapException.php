<?php

declare(strict_types=1);

namespace AdvClientAPI\Exceptions;

/**
 * Exception thrown when SOAP requests fail
 */
class SoapException extends InsuranceApiException
{
    private string $soapFault = '';
    private string $action = '';
    private string $requestBody = '';

    public function __construct(
        string $message,
        int $code = 0,
        string $soapFault = '',
        string $action = '',
        string $requestBody = ''
    ) {
        parent::__construct($message, $code);
        $this->soapFault = $soapFault;
        $this->action = $action;
        $this->requestBody = self::maskSensitiveData($requestBody);
    }

    public function getSoapFault(): string
    {
        return $this->soapFault;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getRequestBody(): string
    {
        return $this->requestBody;
    }
}
