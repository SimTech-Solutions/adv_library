<?php

declare(strict_types=1);

namespace AdvClientAPI\Mappers;

use AdvClientAPI\Exceptions\ResponseParsingException;

/**
 * Maps cancel eligibility SOAP responses to array format
 * Handles SOAP-level errors
 */
class CancelEligibilityResponseMapper extends BaseResponseMapper
{
    /**
     * Map SOAP cancellation response to array
     *
     * @return array<string, mixed>
     * @throws ResponseParsingException
     */
    public function map(): array
    {
        try {
            // Extract response-level fields FIRST to check for errors
            $returnCode = $this->findTextAnywhere('returnCode');
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
            if ($results === null) {
                return ['error' => 'No results found'];
            }

            return [
                'success' => true,
                'returnCode' => $returnCode,
                'elegibilityNbr' => $this->safeInt($this->findText($results, 'elegibilityNbr')),
                'buID' => $this->findText($results, 'buID'),
                'memID' => $this->findText($results, 'memID'),
                'providerID' => $this->findText($results, 'providerID'),
            ];
        } catch (ResponseParsingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ResponseParsingException(
                "Error mapping cancel eligibility response: {$e->getMessage()}",
                '',
                'SOAP'
            );
        }
    }
}
