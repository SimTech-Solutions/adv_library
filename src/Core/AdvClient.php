<?php

declare(strict_types=1);

namespace AdvClientAPI\Core;

use AdvClientAPI\Services\AdvanceCareService;
use AdvClientAPI\Services\AdvanceCareOracleService;
use Exception;

/**
 * Main entry point for the ADV Insurance API PHP library
 *
 * Provides a minimal, clean interface requiring only JSON input data
 * Handles all complexity internally including:
 * - SOAP and REST API communication
 * - Template rendering for SOAP bodies
 * - OAuth2 token management with caching
 * - Response parsing and mapping
 * - Retry logic with exponential backoff
 * - Configuration management
 */
class AdvClient
{
    private AdvanceCareService $advanceCareService;
    private AdvanceCareOracleService $oracleService;
    // private Config $config;

    /**
     * Constructor
     * 
     * This main Constructor Defaults the configuration to Production configurations
     * 
     * Use AdvClient::testInstance() for testing configurations
     */
    public function __construct()
    {
        // $this->config = new Config();
        $this->advanceCareService = new AdvanceCareService(new Config());
        $this->oracleService = new AdvanceCareOracleService(new Config());
    }
    public static function testInstance(): self
    {
        $config = Config::testInstance();
    
        $client = new self();
        // Replace the default Config instances with test Config
        $client->advanceCareService = new AdvanceCareService($config);
        $client->oracleService = new AdvanceCareOracleService($config);

        return $client;
    }
    // ==================== ADVANCECARE SOAP OPERATIONS ====================

    /**
     * Perform pharma act operation (SOAP)
     *
     * Validates pharmaceutical action eligibility and returns eligibility ID
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     *
     * @example
     * ```php
     * $client = new AdvClient();
     * $response = $client->performPharmaAct([
     *     "buID" => "ESA",
     *     "currencyCode" => "AOA",
     *     "dos" => "2025-12-06T10:00:00Z",
     *     "pharmaServiceValuesList" => [
     *         [
     *             "amtClaimed" => "123.45",
     *             "procCode" => "PROC1",
     *             "iva" => "12.35",
     *             "unit" => 1
     *         ],
     *         [
     *             "amtClaimed" => "67.89",
     *             "procCode" => "PROC2",
     *             "iva" => "6.79",
     *             "unit" => 2
     *         ]
     *     ],
     *     "memID" => "MEMBER123",
     *     "practiceSeq" => 12345,
     *     "providerID" => "PROVIDER123",
     *     "username" => "testuser",
     *     "password" => "testpassword",
     *     "created" => "2025-12-06T10:00:00Z"
     * ]);
     * ```
     */
    public function performPharmaAct(array $jsonData): array

    {
       

        $result = $this->advanceCareService->performPharmaAct($jsonData);;
        
    

        return $result;
    }

    /**
     * Add invoice operation (SOAP)
     *
     * Adds invoice information to an existing eligibility
     *
     * Required fields:
     * - username: Authentication username
     * - password: Authentication password
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     *
     * @example
     * ```php
     * $client = new AdvClient();
     * $response = $client->addInvoice([
     *     "eligibilityNbr" => 123456789,
     *     "memClinicId" => "INVOICE-123",
     *     "userId" => "testuser",
     *     "username" => "testuser",
     *     "password" => "testpassword",
     *     "created" => "2025-12-06T10:00:00Z"
     * ]);
     * ```
     */
    public function addInvoice(array $jsonData): array
    {
        return $this->advanceCareService->addInvoice($jsonData);
    }

    /**
     * Create eligibility operation (SOAP)
     *
     * Creates a new eligibility for a single act
     *
     * Required fields:
     * - username: Authentication username
     * - password: Authentication password
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     *
     * @example
     * ```php
     * $client = new AdvClient();
     * $response = $client->createEligibility([
     *     "buID" => "ESA",
     *     "dos" => "2025-12-06T10:00:00Z",
     *     "memID" => "MEMBER123",
     *     "providerID" => "PROVIDER123",
     *     "username" => "testuser",
     *     "password" => "testpassword",
     *     "created" => "2025-12-06T10:00:00Z"
     * ]);
     * ```
     */
    public function createEligibility(array $jsonData): array
    {
        throw new Exception("Feature not Implemeneted");
        // return $this->advanceCareService->createEligibility($jsonData);
    }

    /**
     * Cancel eligibility operation (SOAP)
     *
     * Cancels an existing eligibility
     *
     * Required fields:
     * - username: Authentication username
     * - password: Authentication password
     * - dos: Date of service (YYYY-MM-DD)
     * - eligibility_id: ID of eligibility to cancel
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     *
     * @example
     * ```php
     * $client = new AdvClient();
     * $response = $client->cancelEligibility([
     *     "eligibilityNbr" => 123456789,
     *     "username" => "testuser",
     *     "password" => "testpassword",
     *     "created" => "2025-12-06T10:00:00Z"
     * ]);
     * ```
     */
    public function cancelEligibility(array $jsonData): array
    {
        return $this->advanceCareService->cancelEligibility($jsonData);
    }

    // ==================== ORACLE REST OPERATIONS ====================

    /**
     * Perform pharma act operation (Oracle REST)
     *
     * Validates pharmaceutical action eligibility via Oracle REST API
     * Automatically manages OAuth2 token with caching
     *
     * Required fields:
     * - auth.clientId: OAuth2 client ID
     * - auth.clientSecret: OAuth2 client secret
     * - auth.providerId: Provider ID for endpoint
     * - requestData: Request payload
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     *
     * @example
     * ```php
     * $client = new AdvClient();
     * $response = $client->oraclePerformPharmaAct([
     *     "payerCode" => "VIV",
     *     "insuranceType" => "S",
     *     "userName" => "Provideruserone",
     *     "memberCode" => "99999993000202",
     *     "localCode" => "AO5000078271-2",
     *     "locationType" => "FARMA",
     *     "memberPhoneNo" => "925334548",
     *     "emergency" => false,
     *     "claimDiagnosisList" => [
     *         [
     *             "sequence" => 1,
     *             "diagnosisType" => "P",
     *             "diagnosisDate" => "2026-02-13",
     *             "symptomsDate" => "2026-02-13",
     *             "diagnosisCode" => "B50",
     *             "classification" => "CID10"
     *         ]
     *     ],
     *     "claimLineList" => [
     *         [
     *             "sequence" => "1",
     *             "medicalActCode" => "P-0010325",
     *             "startDate" => "2026-02-13",
     *             "endDate" => "2026-02-13",
     *             "requestedUnits" => 1,
     *             "requestedAmount" => [
     *                 "value" => "12500",
     *                 "currency" => "AOA"
     *             ]
     *         ]
     *     ]
     * ]);
     * ```
     */
    public function oraclePerformPharmaAct(array $jsonData): array
    {
            //    $result =
     return $this->oracleService->performPharmaAct($jsonData);
        
    // print('Printing the result: ');
    // var_dump($result);
        // return $result;  }
    }
    /**
     * Add invoice operation (Oracle REST)
     *
     * Adds invoice information via Oracle REST API
     * Automatically manages OAuth2 token with caching
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     *
     * @example
     * ```php
     * $client = new AdvClient();
     * $response = $client->oracleAddInvoice([
     *     "payerCode" => "VIV",
     *     "claimCode" => "47122295764",
     *     "invoiceNumber" => "INV-2026-001",
     *     "userName" => "Provideruserone"
     * ]);
     * ```
     */
    public function oracleAddInvoice(array $jsonData): array
    {
        return $this->oracleService->addInvoice($jsonData);
    }

    /**
     * Create eligibility operation (Oracle REST)
     *
     * Creates new eligibility via Oracle REST API
     * Automatically manages OAuth2 token with caching
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     *
     * @example
     * ```php
     * $client = new AdvClient();
     * $response = $client->oracleCreateEligibility([
     *     "payerCode" => "VIV",
     *     "memberCode" => "99999993000202",
     *     "userName" => "Provideruserone"
     * ]);
     * ```
     */
    public function oracleCreateEligibility(array $jsonData): array
    {
        return $this->oracleService->createEligibility($jsonData);
    }

    /**
     * Cancel eligibility operation (Oracle REST)
     *
     * Cancels eligibility via Oracle REST API
     * Automatically manages OAuth2 token with caching
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     *
     * @example
     * ```php
     * $client = new AdvClient();
     * $response = $client->oracleCancelEligibility([
     *     "payerCode" => "VIV",
     *     "memberCode" => "PT1410202501-02",
     *     "localCode" => "AO5000078271-1",
     *     "claimCode" => "47122295764",
     *     "cancellationReasonCode" => "CAN_DUP",
     *     "userName" => "Provideruserone"
     * ]);
     * ```
     */
    public function oracleCancelEligibility(array $jsonData): array
    {
        return $this->oracleService->cancelEligibility($jsonData);
    }
}
// Alias for backward compatibility
class_alias(AdvClient::class, 'AdvClientAPI\\Core\\InsuranceApiClient');
