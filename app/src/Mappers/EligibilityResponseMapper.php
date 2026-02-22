<?php

declare(strict_types=1);

namespace AdvClientAPI\Mappers;

use AdvClientAPI\Exceptions\ResponseParsingException;

/**
 * Maps eligibility creation SOAP responses to array format
 * Extracts acts and handles SOAP-level errors
 */
class EligibilityResponseMapper extends BaseResponseMapper
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
            if ($results === null) {
                return ['error' => 'No results found'];
            }

            // Extract multiple acts
            $acts = $this->extractActs($results);

            return [
                'success' => true,
                'returnCode' => $returnCode,
                'totalResults' => $this->safeInt($totalResults),
                'elegibilityNbr' => $this->safeInt($this->findText($results, 'elegibilityNbr')),
                'memID' => $this->findText($results, 'memID'),
                'memFullName' => $this->findText($results, 'memFullName'),
                'providerName' => $this->findText($results, 'providerName'),
                'clinicName' => $this->findText($results, 'clinicName'),
                'totalAmtCopay' => $this->toDecimal($this->findText($results, 'totalAmtCopay')),
                'totalAmtPaid' => $this->toDecimal($this->findText($results, 'totalAmtPaid')),
                'acts' => $acts,
            ];
        } catch (ResponseParsingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ResponseParsingException(
                "Error mapping eligibility response: {$e->getMessage()}",
                '',
                'SOAP'
            );
        }
    }

    /**
     * Extract all eligibility service acts
     *
     * @param \SimpleXMLElement $results
     * @return array<array<string, mixed>>
     */
    private function extractActs(\SimpleXMLElement $results): array
    {
        $acts = [];
        $actElements = $this->findAll($results, './/eligibilityServicesValuesReturn');

        foreach ($actElements as $act) {
            $acts[] = [
                'procCode' => $this->findText($act, 'procCode'),
                'serviceCode' => $this->safeInt($this->findText($act, 'serviceCode')),
                'amtClaimed' => $this->toDecimal($this->findText($act, 'amtClaimed')),
                'amtCopay' => $this->toDecimal($this->findText($act, 'amtCopay')),
                'amtPaid' => $this->toDecimal($this->findText($act, 'amtPaid')),
                'totalIva' => $this->toDecimal($this->findText($act, 'totalIva')),
            ];
        }

        return $acts;
    }
}
