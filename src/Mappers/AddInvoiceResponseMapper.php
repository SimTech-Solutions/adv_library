<?php

declare(strict_types=1);

namespace AdvClientAPI\Mappers;

use AdvClientAPI\Exceptions\ResponseParsingException;

/**
 * Maps add invoice SOAP responses to array format
 * Handles SOAP-level errors
 */
class AddInvoiceResponseMapper extends BaseResponseMapper
{
    /**
     * Map SOAP response to array
     *
     * @return array<string, mixed>
     * @throws ResponseParsingException
     */
    public function map(): array
    {
        try {
            // Extract response-level fields FIRST to check for errors
            $returnCode = $this->findTextAnywhere('returnCode');
            $totalResults = $this->findTextAnywhere('totalResults');
            $messages = $this->extractMessages();

            // Check for SOAP-level errors
            if ($returnCode && $returnCode !== '0') {
                $errorDetail = !empty($messages) ? $messages[0] : 'Unknown error';
                $errorJson = json_encode([
                    'error' => 'SOAP Error',
                    'detail' => $errorDetail,
                    'returnCode' => $returnCode,
                    'messages' => $messages,
                ], JSON_UNESCAPED_UNICODE);
                throw new ResponseParsingException(
                    "SOAP Error: {$errorJson}",
                    '',
                    'SOAP'
                );
            }

            $results = $this->findElement('.//results');

            return [
                'success' => true,
                'returnCode' => $returnCode,
                'totalResults' => $totalResults,
                'results' => $results !== null,
            ];
        } catch (ResponseParsingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ResponseParsingException(
                "Error mapping add invoice response: {$e->getMessage()}",
                '',
                'SOAP'
            );
        }
    }
}
