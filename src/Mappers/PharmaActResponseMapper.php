<?php

declare(strict_types=1);

namespace AdvClientAPI\Mappers;

use AdvClientAPI\Exceptions\ResponseParsingException;

/**
 * Maps pharma act SOAP responses to array format
 * Extracts services, ICD9 codes, and handles SOAP-level errors
 */
class PharmaActResponseMapper extends BaseResponseMapper
{
    /**
     * Map SOAP response to array
     * Includes totals, service list, and fields from first service
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
           return['success'=>false,
                    'error' => 'SOAP Error',
                    'detail' => $errorDetail,
                    'returnCode' => $returnCode,
                    'messages' => $messages,
                    'totalResults'=>$totalResults
                ];
                // throw new ResponseParsingException(
                //     "SOAP Error: {$errorJson}",
                //     '',
                //     'SOAP'
                // );
            }

            // Find main results element
            $resultsElement = $this->findElement('.//results');
            if ($resultsElement === null) {
                return [
                    'success' => true,
                    'returnCode' => $this->safeInt($returnCode),
                    'totalResults' => $this->safeInt($totalResults),
                    'results' => null,
                ];
            }

            // Extract services and ICD9 codes
            $services = $this->extractEligibilityServices($resultsElement);
            $icd9Codes = $this->extractIcd9Codes($resultsElement);

            // Build response mapping
            $resultsData = [
                'assistantName' => $this->findText($resultsElement, 'assistantName'),
                'buName' => $this->findText($resultsElement, 'buName'),
                'catCode' => $this->findText($resultsElement, 'catCode'),
                'catName' => $this->findText($resultsElement, 'catName'),
                'clinicName' => $this->findText($resultsElement, 'clinicName'),
                'currencyCode' => $this->findText($resultsElement, 'currencyCode'),
                'dos' => $this->findText($resultsElement, 'dos'),
                'elegibilityNbr' => $this->safeInt($this->findText($resultsElement, 'elegibilityNbr')),
                'memID' => $this->findText($resultsElement, 'memID'),
                'memFullName' => $this->findText($resultsElement, 'memFullName'),
                'payeeId' => $this->findText($resultsElement, 'payeeId'),
                'payeeToId' => $this->findText($resultsElement, 'payeeToId'),
                'practiceSeq' => $this->safeInt($this->findText($resultsElement, 'practiceSeq')),
                'providerID' => $this->findText($resultsElement, 'providerID'),
                'providerName' => $this->findText($resultsElement, 'providerName'),
                'specialtyDesc' => $this->findText($resultsElement, 'specialtyDesc'),
                'totalAmtCopay' => $this->toDecimal($this->findText($resultsElement, 'totalAmtCopay')),
                'totalAmtPaid' => $this->toDecimal($this->findText($resultsElement, 'totalAmtPaid')),
                'totalPagoComIva' => $this->toDecimal($this->findText($resultsElement, 'totalPagoComIva')),
                'userId' => $this->findText($resultsElement, 'userId'),
                'memPhone' => $this->findText($resultsElement, 'memPhone'),
                'memEmail' => $this->findText($resultsElement, 'memEmail'),
                'icd9Codes' => $icd9Codes,
                'eligibilityServicesValuesReturn' => $services,
            ];

            return [
                'success' => true,
                'returnCode' => $this->safeInt($returnCode),
                'totalResults' => $this->safeInt($totalResults),
                'results' => $resultsData,
            ];
        } catch (ResponseParsingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ResponseParsingException(
                "Error mapping pharma act response: {$e->getMessage()}",
                '',
                'SOAP'
            );
        }
    }

    /**
     * Extract all eligibility service values
     *
     * @param \SimpleXMLElement $results
     * @return array<array<string, mixed>>
     */
    private function extractEligibilityServices(\SimpleXMLElement $results): array
    {
        $services = [];
        $serviceElements = $this->findAll($results, './/eligibilityServicesValuesReturn');

        foreach ($serviceElements as $service) {
            $services[] = [
                'procCode' => $this->findText($service, 'procCode'),
                'serviceCode' => $this->findText($service, 'serviceCode'),
                'unit' => $this->safeFloat($this->findText($service, 'unit')),
                'amtClaimed' => $this->toDecimal($this->findText($service, 'amtClaimed')),
                'amtCopay' => $this->toDecimal($this->findText($service, 'amtCopay')),
                'amtPaid' => $this->toDecimal($this->findText($service, 'amtPaid')),
                'totalIva' => $this->toDecimal($this->findText($service, 'totalIva')),
                'aggravationRate' => $this->safeInt($this->findText($service, 'aggravationRate')),
                'anatomicRegion' => $this->findText($service, 'anatomicRegion'),
                'incidence' => $this->findText($service, 'incidence'),
                'laterality' => $this->findText($service, 'laterality'),
                'load' => $this->findText($service, 'load'),
            ];
        }

        return $services;
    }

    /**
     * Extract all ICD9 diagnosis codes
     *
     * @param \SimpleXMLElement $results
     * @return array<array<string, string|null>>
     */
    private function extractIcd9Codes(\SimpleXMLElement $results): array
    {
        $codes = [];
        $icd9Elements = $this->findAll($results, './/icd9Codes');

        foreach ($icd9Elements as $elem) {
            $diagCode = $this->findText($elem, 'diagCode');
            if ($diagCode) {
                $codes[] = ['diagCode' => $diagCode];
            }
        }

        return $codes;
    }
}
