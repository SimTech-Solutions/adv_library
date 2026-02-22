<?php

declare(strict_types=1);

namespace AdvClientAPI\Mappers;

use AdvClientAPI\Exceptions\ResponseParsingException;

/**
 * Maps Oracle REST API JSON responses to array format
 * Extracts claim details, line items, and diagnosis information
 */
class OracleResponseMapper implements \AdvClientAPI\Contracts\ResponseMapperInterface
{
    /** @var array<string, mixed> */
    private array $response;

    /**
     * Constructor
     *
     * @param string $jsonContent JSON response content
     * @throws ResponseParsingException
     */
    public function __construct(string $jsonContent)
    {
        $decoded = json_decode($jsonContent, true);
        if (!is_array($decoded)) {
            throw new ResponseParsingException(
                'Invalid JSON response',
                $jsonContent,
                'JSON'
            );
        }
        $this->response = $decoded;
    }

    /**
     * Map Oracle REST response to array
     *
     * @return array<string, mixed>
     * @throws ResponseParsingException
     */
    public function map(): array
    {
        try {
            $payload = $this->response['payload'] ?? [];
            $status = $payload['status'] ?? 'FAILED';
            $isSuccess = $status === 'SUCCESS';

            // Extract event payload information
            $eventPayload = $payload['eventpayload'] ?? [];
            $claims = $eventPayload['claim'] ?? [];

            // Map all claims
            $mappedClaims = [];
            if (is_array($claims)) {
                foreach ($claims as $claim) {
                    $mappedClaims[] = $this->mapClaim($claim);
                }
            }

            return [
                'success' => $isSuccess,
                'status' => $status,
                'code' => $eventPayload['code'] ?? null,
                'claimCode' => $eventPayload['claimCode'] ?? null,
                'event' => $eventPayload['event'] ?? null,
                'level' => $eventPayload['level'] ?? null,
                'oigExchangeId' => $eventPayload['oigExchangeId'] ?? null,
                'topic' => $eventPayload['topic'] ?? null,
                'timestamp' => $eventPayload['timestamp']['value'] ?? null,
                'claims' => $mappedClaims,
            ];
        } catch (ResponseParsingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ResponseParsingException(
                "Error mapping Oracle response: {$e->getMessage()}",
                json_encode($this->response),
                'JSON'
            );
        }
    }

    /**
     * Map individual claim object
     *
     * @param array<string, mixed> $claim
     * @return array<string, mixed>
     */
    private function mapClaim(array $claim): array
    {
        return [
            'code' => $claim['code'] ?? null,
            'status' => $claim['status'] ?? null,
            'payerCode' => $claim['payerCode'] ?? null,
            'lineOfBusiness' => $claim['lineOfBusiness'] ?? null,
            'memberCode' => $claim['memberCode'] ?? null,
            'memberName' => $claim['memberName'] ?? null,
            'localCode' => $claim['localCode'] ?? null,
            'paymentReceiver' => $claim['paymentReceiver'] ?? null,
            'locationType' => $claim['locationType'] ?? null,
            'emergency' => $claim['emergency'] ?? false,
            'externalClaimsData' => $claim['externalClaimsdata'] ?? null,
            'advPortalLink' => $claim['advPortalLink'] ?? null,
            'amounts' => $this->mapAmounts($claim),
            'diagnoses' => $this->mapDiagnoses($claim['claimDiagnosisList'] ?? []),
            'lineItems' => $this->mapLineItems($claim['claimLineList'] ?? []),
            'messages' => $claim['claimMessageList'] ?? [],
            'pendReasons' => $claim['claimPendReasonList'] ?? [],
        ];
    }

    /**
     * Extract amount fields from claim
     *
     * @param array<string, mixed> $claim
     * @return array<string, mixed>
     */
    private function mapAmounts(array $claim): array
    {
        return [
            'totalRequestedAmount' => $this->extractAmountValue($claim['totalRequestedAmount'] ?? null),
            'totalAllowedAmount' => $this->extractAmountValue($claim['totalAllowedAmount'] ?? null),
            'totalMemberCoPayAmount' => $this->extractAmountValue($claim['totalMemberCoPayAmount'] ?? null),
            'totalNotCoveredAmount' => $this->extractAmountValue($claim['totalNotCoveredAmount'] ?? null),
            'totalMemberShareAmount' => $this->extractAmountValue($claim['totalMemberShareAmount'] ?? null),
            'totalApprovedAmount' => $this->extractAmountValue($claim['totalApprovedAmount'] ?? null),
            'totalIvaApprovedAmount' => $this->extractAmountValue($claim['totalIvaApprovedAmount'] ?? null),
            'totalPaymentAmount' => $this->extractAmountValue($claim['totalPaymentAmount'] ?? null),
        ];
    }

    /**
     * Extract value from amount object
     *
     * @param array<string, mixed>|null $amount
     * @return float|null
     */
    private function extractAmountValue(?array $amount): ?float
    {
        if (!is_array($amount)) {
            return null;
        }
        $value = $amount['value'] ?? null;
        if ($value === null) {
            return null;
        }
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Map diagnosis list
     *
     * @param array<array<string, mixed>> $diagnoses
     * @return array<array<string, mixed>>
     */
    private function mapDiagnoses(array $diagnoses): array
    {
        $mapped = [];
        foreach ($diagnoses as $diagnosis) {
            $mapped[] = [
                'code' => $diagnosis['diagnosisCode'] ?? null,
                'type' => $diagnosis['diagnosisType'] ?? null,
                'sequence' => $diagnosis['sequence'] ?? null,
                'date' => $diagnosis['diagnosisDate'] ?? null,
                'symptomsDate' => $diagnosis['symptomsDate'] ?? null,
                'classification' => $diagnosis['classification'] ?? null,
            ];
        }
        return $mapped;
    }

    /**
     * Map claim line items
     *
     * @param array<array<string, mixed>> $lineItems
     * @return array<array<string, mixed>>
     */
    private function mapLineItems(array $lineItems): array
    {
        $mapped = [];
        foreach ($lineItems as $line) {
            $mapped[] = [
                'sequence' => $line['sequence'] ?? null,
                'medicalActCode' => $line['medicalActCode'] ?? null,
                'serviceType' => $line['serviceType'] ?? null,
                'status' => $line['status'] ?? null,
                'startDate' => $line['startDate'] ?? null,
                'endDate' => $line['endDate'] ?? null,
                'requestedUnits' => $line['requestedUnits'] ?? null,
                'amounts' => [
                    'requestedAmount' => $this->extractAmountValue($line['requestedAmount'] ?? null),
                    'allowedAmount' => $this->extractAmountValue($line['allowedAmount'] ?? null),
                    'memberCoPayAmount' => $this->extractAmountValue($line['memberCoPayAmount'] ?? null),
                    'notCoveredAmount' => $this->extractAmountValue($line['notCoveredAmount'] ?? null),
                    'memberShareAmount' => $this->extractAmountValue($line['memberShareAmount'] ?? null),
                    'approvedAmount' => $this->extractAmountValue($line['approvedAmount'] ?? null),
                    'ivaApprovedAmount' => $this->extractAmountValue($line['ivaApprovedAmount'] ?? null),
                    'paymentAmount' => $this->extractAmountValue($line['paymentAmount'] ?? null),
                ],
                'messages' => $line['claimLineMessageList'] ?? [],
                'pendReasons' => $line['claimLinePendReasonList'] ?? [],
                'serviceComponents' => $line['serviceComponents'] ?? [],
                'notes' => $line['lineNotes'] ?? null,
            ];
        }
        return $mapped;
    }
}
