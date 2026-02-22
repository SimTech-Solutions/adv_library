<?php

declare(strict_types=1);

namespace AdvClientAPI\Exceptions;

use Exception;

/**
 * Base exception for all insurance API library exceptions
 */
class InsuranceApiException extends Exception
{
    /**
     * Mask sensitive data from strings
     *
     * @param string $data
     * @return string
     */
    protected static function maskSensitiveData(string $data): string
    {
        // Mask passwords
        $data = preg_replace(
            '/password[\s]*[:=][\s]*[^\s,}]+/i',
            'password=***MASKED***',
            $data
        );

        // Mask tokens
        $data = preg_replace(
            '/token[\s]*[:=][\s]*[^\s,}]+/i',
            'token=***MASKED***',
            $data
        );

        // Mask Authorization headers
        $data = preg_replace(
            '/Authorization[\s]*:[\s]*Bearer\s+[^\s]+/i',
            'Authorization: Bearer ***MASKED***',
            $data
        );

        // Mask client secrets
        $data = preg_replace(
            '/clientSecret[\s]*[:=][\s]*[^\s,}]+/i',
            'clientSecret=***MASKED***',
            $data
        );

        return $data;
    }
}
