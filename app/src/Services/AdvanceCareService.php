<?php

declare(strict_types=1);

namespace AdvClientAPI\Services;

use AdvClientAPI\Core\Config;
use AdvClientAPI\Exceptions\SoapException;
use AdvClientAPI\Mappers\{
    PharmaActResponseMapper,
    EligibilityResponseMapper,
    AddInvoiceResponseMapper,
    CancelEligibilityResponseMapper
};
use AdvClientAPI\Templates\TemplateRenderer;
use AdvClientAPI\Utilities\DateFormatter;

/**
 * AdvanceCare SOAP service implementation
 */
class AdvanceCareService extends BaseService
{
    private TemplateRenderer $renderer;

    // SOAP constants
    private const SOAP_ACTION_PHARMA = 'performPharmaActRequest';
    private const SOAP_ACTION_ELIGIBILITY = 'performSingleActRequest';
    private const SOAP_ACTION_ADD_INVOICE = 'addInvoiceNumberRequest';
    private const SOAP_ACTION_CANCEL = 'nullifyEligibilityOrSingleActRequest';

    private const SOAP_NAMESPACE = 'http://pt.advancecare.awsp.eligibilityAO';
    private const SOAP_VERSION = '1.2';

    public function __construct(Config $config)
    {
        parent::__construct($config);

        // Initialize template renderer with templates directory
        $templatesPath = dirname(__DIR__) . '/Templates';
        $this->renderer = new TemplateRenderer($templatesPath);
    }

    /**
     * Perform pharma act request
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws SoapException
     */
    public function performPharmaAct(array $data): array
    {
        $this->validatePharmaActData($data);

        // Extract security fields and data
        $renderData = $this->prepareRenderData($data);

        $soapBody = $this->renderer->render('advancecare/pharma_act.xml.j2', $renderData);

        $response = $this->sendSoap(
            $soapBody,
            self::SOAP_ACTION_PHARMA
        );

        $mapper = new PharmaActResponseMapper($response);
        return $mapper->map();
    }

    /**
     * Create eligibility request
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws SoapException
     */
    public function createEligibility(array $data): array
    {
        $this->validateEligibilityData($data);

        // Extract security fields and data
        $renderData = $this->prepareRenderData($data);

        $soapBody = $this->renderer->render('advancecare/create_eligibility.xml.j2', $renderData);

        $response = $this->sendSoap(
            $soapBody,
            self::SOAP_ACTION_ELIGIBILITY
        );

        $mapper = new EligibilityResponseMapper($response);
        return $mapper->map();
    }

    /**
     * Add invoice request
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws SoapException
     */
    public function addInvoice(array $data): array
    {
        $this->validateInvoiceData($data);

        // Extract security fields and data
        $renderData = $this->prepareRenderData($data);

        $soapBody = $this->renderer->render('advancecare/add_invoice.xml.j2', $renderData);

        $response = $this->sendSoap(
            $soapBody,
            self::SOAP_ACTION_ADD_INVOICE
        );

        $mapper = new AddInvoiceResponseMapper($response);
        return $mapper->map();
    }

    /**
     * Cancel eligibility request
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws SoapException
     */
    public function cancelEligibility(array $data): array
    {
        $this->validateCancelData($data);

        // Extract security fields and data
        $renderData = $this->prepareRenderData($data);

        $soapBody = $this->renderer->render('advancecare/cancel_eligibility.xml.j2', $renderData);

        $response = $this->sendSoap(
            $soapBody,
            self::SOAP_ACTION_CANCEL
        );

        $mapper = new CancelEligibilityResponseMapper($response);
        return $mapper->map();
    }

    /**
     * Prepare data for template rendering
     * Separates security fields from request data and restructures for template
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareRenderData(array $data): array
    {
        // Extract security fields
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        // Create render data structure expected by templates
        $renderData = [
            'username' => $username,
            'password' => $password,
            'created' => DateFormatter::getCurrentIso8601($data['dos']),
            // 'expires' => DateFormatter::formatTimestamp(time() + 3600), // 1 hour expiration
            'timestamp_id' => bin2hex(random_bytes(8)),
            'data' => $data  // All original data nested under 'data' key
        ];

        return $renderData;
    }

    /**
     * Send SOAP request
     *
     * @param string $soapBody
     * @param string $action
     * @return string Response body
     * @throws SoapException
     */
    private function sendSoap(string $soapBody, string $action): string
    {
        $url = $this->config->getAdvanceCareUrl();

        $headers = [
            'Content-Type' => 'application/soap+xml; charset=UTF-8',
            'SOAPAction' => $action,
        ];

        $this->logger->debug('Sending SOAP request', [
            'action' => $action,
            'url' => $url,
        ]);

        try {
            $response = $this->makeRequest('POST', $url, $headers, $soapBody);

            if ($response['status_code'] < 200 || $response['status_code'] >= 300) {
                throw new SoapException(
                    "SOAP request failed with HTTP {$response['status_code']}",
                    $response['status_code'],
                    $response['body'],
                    $action,
                    $soapBody
                );
            }

            // Check for SOAP faults in response
            if (stripos($response['body'], 'soap:Fault') !== false) {
                throw new SoapException(
                    "SOAP Fault in response",
                    0,
                    $response['body'],
                    $action,
                    $soapBody
                );
            }

            return $response['body'];
        } catch (SoapException $e) {
            $this->logger->error('SOAP request failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            throw new SoapException(
                "SOAP request error: {$e->getMessage()}",
                0,
                '',
                $action,
                $soapBody
            );
        }
    }

    /**
     * Validate pharma act data
     *
     * @param array<string, mixed> $data
     * @throws \InvalidArgumentException
     */
    private function validatePharmaActData(array $data): void
    {
        $required = ['username', 'password', 'customer_code', 'dos', 'nif', 'practitioner_code', 'pharmacy_code', 'beneficiary_code'];
        $this->validateRequiredFields($data, $required);

        // Format DOS
        if (!empty($data['dos'])) {
            $data['dos'] = DateFormatter::formatDosDate($data['dos']);
        }
    }

    /**
     * Validate eligibility data
     *
     * @param array<string, mixed> $data
     * @throws \InvalidArgumentException
     */
    private function validateEligibilityData(array $data): void
    {
        $required = ['username', 'password', 'customer_code', 'dos', 'nif', 'practitioner_code', 'act_code', 'beneficiary_code'];
        $this->validateRequiredFields($data, $required);

        if (!empty($data['dos'])) {
            $data['dos'] = DateFormatter::formatDosDate($data['dos']);
        }
    }

    /**
     * Validate invoice data
     *
     * @param array<string, mixed> $data
     * @throws \InvalidArgumentException
     */
    private function validateInvoiceData(array $data): void
    {
        $required = ['username', 'password', 'customer_code', 'dos', 'invoice_number', 'invoice_date', 'invoice_amount'];
        $this->validateRequiredFields($data, $required);

        if (!empty($data['dos'])) {
            $data['dos'] = DateFormatter::formatDosDate($data['dos']);
        }
    }

    /**
     * Validate cancel data
     *
     * @param array<string, mixed> $data
     * @throws \InvalidArgumentException
     */
    private function validateCancelData(array $data): void
    {
        $required = ['username', 'password', 'customer_code', 'dos', 'eligibility_id'];
        $this->validateRequiredFields($data, $required);

        if (!empty($data['dos'])) {
            $data['dos'] = DateFormatter::formatDosDate($data['dos']);
        }
    }

    /**
     * Validate that required fields exist in data
     *
     * @param array<string, mixed> $data
     * @param array<string> $required
     * @throws \InvalidArgumentException
     */
    private function validateRequiredFields(array $data, array $required): void
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }
}
