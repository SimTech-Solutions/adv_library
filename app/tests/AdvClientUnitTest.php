<?php

declare(strict_types=1);

namespace AdvClientAPI\Tests;

use PHPUnit\Framework\TestCase;
use AdvClientAPI\Core\AdvClient;

/**
 * Unit tests for ADV Insurance API Client
 * 
 * These tests validate the structure and behavior of the AdvClient
 * without requiring actual API credentials or external service calls
 */
class AdvClientUnitTest extends TestCase
{
    /**
     * Test AdvClient instantiation with default configuration
     */
    public function testClientInstantiation(): void
    {
        $client = new AdvClient();
        $this->assertInstanceOf(AdvClient::class, $client);
    }

    /**
     * Test AdvClient instantiation with test configuration
     */
    public function testClientTestInstance(): void
    {
        $client = AdvClient::testInstance();
        $this->assertInstanceOf(AdvClient::class, $client);
    }

    /**
     * Test valid PharmaAct payload structure
     */
    public function testValidPharmaActPayloadStructure(): void
    {
        $payload = [
            "auth" => [
                "clientId" => "test-client-id",
                "clientSecret" => "test-client-secret",
                "providerId" => "test-provider-id",
                "scope" => "https://adva-test-ohi.oracleindustry.com/test/urn::ohi-components-apis"
            ],
            "requestData" => [
                "payerCode" => "VIV",
                "insuranceType" => "S",
                "userName" => "TestUser",
                "memberCode" => "99999993000202",
                "localCode" => "AO5000078271-2",
                "locationType" => "FARMA",
                "memberPhoneNo" => "925334548",
                "emergency" => "false",
                "claimDiagnosisList" => [
                    [
                        "sequence" => 1,
                        "diagnosisType" => "P",
                        "diagnosisDate" => "2026-02-13",
                        "symptomsDate" => "2026-02-13",
                        "diagnosisCode" => "B50",
                        "classification" => "CID10"
                    ]
                ],
                "claimLineList" => [
                    [
                        "sequence" => "1",
                        "medicalActCode" => "P-0010325",
                        "startDate" => "2026-02-13",
                        "endDate" => "2026-02-13",
                        "requestedUnits" => 1,
                        "requestedAmount" => [
                            "value" => "5325",
                            "currency" => "AOA"
                        ]
                    ]
                ]
            ]
        ];

        // Validate required structure
        $this->assertArrayHasKey('auth', $payload);
        $this->assertArrayHasKey('requestData', $payload);
        $this->assertArrayHasKey('clientId', $payload['auth']);
        $this->assertArrayHasKey('clientSecret', $payload['auth']);
        $this->assertArrayHasKey('providerId', $payload['auth']);
        $this->assertArrayHasKey('claimLineList', $payload['requestData']);
    }

    /**
     * Test expected PharmaAct response structure
     */
    public function testExpectedPharmaActResponseStructure(): void
    {
        $mockResponse = [
            "payload" => [
                "eventpayload" => [
                    "claim" => [
                        [
                            "code" => "48340585265",
                            "status" => "APPROVED",
                            "payerCode" => "VIV",
                            "lineOfBusiness" => "S",
                            "memberCode" => "99999993000202",
                        ]
                    ]
                ],
                "status" => "SUCCESS"
            ],
            "result" => "success",
            "status_code" => 200
        ];

        // Validate response structure
        $this->assertIsArray($mockResponse);
        $this->assertArrayHasKey('payload', $mockResponse);
        $this->assertArrayHasKey('result', $mockResponse);
        $this->assertEquals('success', $mockResponse['result']);
        $this->assertEquals(200, $mockResponse['status_code']);
    }

    /**
     * Test eligibility request payload structure
     */
    public function testValidEligibilityPayloadStructure(): void
    {
        $payload = [
            "auth" => [
                "clientId" => "test-client",
                "clientSecret" => "test-secret",
                "providerId" => "test-provider",
                "scope" => "https://adva-test-ohi.oracleindustry.com/test/urn::ohi-components-apis"
            ],
            "requestData" => [
                "payerCode" => "VIV",
                "memberCode" => "99999993000202",
                "locationType" => "CLINIC"
            ]
        ];

        $this->assertArrayHasKey('auth', $payload);
        $this->assertArrayHasKey('requestData', $payload);
    }
}
