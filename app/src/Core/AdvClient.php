<?php

declare(strict_types=1);

namespace AdvClientAPI\Core;

use AdvClientAPI\Services\AdvanceCareService;
use AdvClientAPI\Services\AdvanceCareOracleService;

/**
 * Main entry point for the Insurance API PHP library
 *
 * Provides a minimal, clean interface requiring only JSON input data
 * Handles all complexity internally including:
 * - SOAP and REST API communication
 * - Template rendering for SOAP bodies
 * - OAuth2 token management with caching
 * - Response parsing and mapping
 * - Retry logic with exponential backoff
 * - Configuration management
 *
 * @example
 * ```php
 * $client = InsuranceApiClient::create([
 *     'advancecare_env' => 'QUAL',
 * ]);
 *
 * $result = $client->performPharmaAct([
 *     'username' => 'user@domain',
 *     'password' => 'secret',
 *     'customer_code' => 'CUST123',
 *     'dos' => '2024-02-20',
 *     'nif' => '12345678-L',
 *     'practitioner_code' => 'PRACT001',
 *     'pharmacy_code' => 'PHARM001',
 *     'beneficiary_code' => 'BEN001',
 * ]);
 *
 * if ($result['success']) {
 *     echo "Eligibility ID: " . $result['eligibility_id'];
 * }
 * ```
 */
class AdvClient
{
    private AdvanceCareService $advanceCareService;
    private AdvanceCareOracleService $oracleService;
    // private Config $config;

    /**
     * Constructor
     *
     * @param Config $config Configuration instance
     */
    public function __construct()
    {   
        // $this->config = new Config();
        $this->advanceCareService = new AdvanceCareService(new Config());
        $this->oracleService = new AdvanceCareOracleService(new Config());
    }

    /**
     * Create client with configuration
     *
     * Static factory method for convenient initialization
     * Configuration sources (priority order):
     * 1. Explicitly passed parameters in $config array
     * 2. Environment variables
     * 3. Default values
     *
     * @param array<string, mixed> $config Configuration array
     * @return self
     *
     * @example
     * ```php
     * $client = InsuranceApiClient::create([
     *     'advancecare_env' => 'PROD',
     *     'http_max_retries' => 5,
     * ]);
     * ```
     */
    // public static function create(array $config = []): self
    // {
    //     // $configInstance = empty($config)
    //     //     ? New Config()
    //         if (empty($config)) {
    //             # code...
    //             $configInstance = new Config();
    //         } 
    //     return new self($configInstance);
    // }

    // ==================== ADVANCECARE SOAP OPERATIONS ====================

    /**
     * Perform pharma act operation (SOAP)
     *
     * Validates pharmaceutical action eligibility and returns eligibility ID
     *
     * Required fields:
     * - username: Authentication username
     * - password: Authentication password
     * - customer_code: Customer code
     * - dos: Date of service (YYYY-MM-DD)
     * - nif: Patient NIF
     * - practitioner_code: Practitioner code
     * - pharmacy_code: Pharmacy code
     * - beneficiary_code: Beneficiary code
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     */
    public function performPharmaAct(array $jsonData): array
    {
        return $this->advanceCareService->performPharmaAct($jsonData);
    }

    /**
     * Add invoice operation (SOAP)
     *
     * Adds invoice information to an existing eligibility
     *
     * Required fields:
     * - username: Authentication username
     * - password: Authentication password
     * - customer_code: Customer code
     * - dos: Date of service (YYYY-MM-DD)
     * - invoice_number: Invoice number
     * - invoice_date: Invoice date (YYYY-MM-DD)
     * - invoice_amount: Invoice amount
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
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
     * - customer_code: Customer code
     * - dos: Date of service (YYYY-MM-DD)
     * - nif: Patient NIF
     * - practitioner_code: Practitioner code
     * - act_code: Act code
     * - beneficiary_code: Beneficiary code
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     */
    public function createEligibility(array $jsonData): array
    {
        return $this->advanceCareService->createEligibility($jsonData);
    }

    /**
     * Cancel eligibility operation (SOAP)
     *
     * Cancels an existing eligibility
     *
     * Required fields:
     * - username: Authentication username
     * - password: Authentication password
     * - customer_code: Customer code
     * - dos: Date of service (YYYY-MM-DD)
     * - eligibility_id: ID of eligibility to cancel
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
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
     * - auth.scope: OAuth2 scope
     * - auth.providerId: Provider ID for endpoint
     * - auth.tokenUrl: (optional) Token endpoint URL
     * - requestData: Request payload
     *
     * @param array<string, mixed> $jsonData
     * @return array<string, mixed>
     * @throws \AdvClientAPI\Exceptions\InsuranceApiException
     */
    public function oraclePerformPharmaAct(array $jsonData): array
    {
        return $this->oracleService->performPharmaAct($jsonData);
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
     */
    public function oracleCancelEligibility(array $jsonData): array
    {
        return $this->oracleService->cancelEligibility($jsonData);
    }

    // ==================== CONFIGURATION MANAGEMENT ====================

    /**
     * Get current configuration
     *
     * @return Config
     */
    // public function getConfig(): Config
    // {
    //     return $this->config;
    // }

    /**
     * Get configuration as array (for debugging/logging)
     *
     * @return array<string, mixed>
     */
    // public function getConfigArray(): array
    // {
    //     return $this->config->toArray();
    // }
}
// Alias for backward compatibility
class_alias(AdvClient::class, 'AdvClientAPI\\Core\\InsuranceApiClient');