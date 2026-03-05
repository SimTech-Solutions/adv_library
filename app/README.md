# ADV Insurance API PHP Library

A robust PHP library for integrating with ADV Insurance web services. Provides seamless handling of both SOAP (AdvanceCare) and REST (Oracle) API endpoints with built-in OAuth2 token management, response mapping, and error handling.

## Installation

### Requirements

- PHP >= 8.2
- Composer
- cURL extension
- JSON extension
- XML extension (for SOAP)

### Install via Composer

```bash
composer require simtech/adv-library
```

## Quick Start

```php
use AdvClientAPI\Core\AdvClient;

// Create client
$client = new AdvClient();

// Prepare request
$payload = [
    "auth" => [
        "clientId" => "YOUR_CLIENT_ID",
        "clientSecret" => "YOUR_CLIENT_SECRET",
        "providerId" => "YOUR_PROVIDER_ID",
        "scope" => "https://adva-prod-ohi.oracleindustry.com/prod/urn::ohi-components-apis"
    ],
    "requestData" => [
        "payerCode" => "VIV",
        "memberCode" => "MEMBER_ID",
        "claimLineList" => [...]
    ]
];

// Execute
try {
    $response = $client->oraclePerformPharmaAct($payload);
    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Features

✅ **Oracle REST API** - PharmaAct, Eligibility, Invoices, Cancellations  
✅ **AdvanceCare SOAP API** - PharmaAct, Invoices, Cancellations  
✅ **OAuth2 Management** - Automatic token handling with caching  
✅ **Response Mapping** - Standardized array format  
✅ **Error Handling** - Comprehensive exception hierarchy  

## Supported Operations

### Oracle REST API (Modern)

All operations below support automatic OAuth2 token management with caching:

#### `oraclePerformPharmaAct(array $payload): array`

Submit a pharmaceutical claim for eligibility evaluation.

**Throws:**

- `AuthException` - Token acquisition or authentication failed
- `OracleException` - API request failed or returned error
- `ResponseParsingException` - Response parsing failed

#### `oracleCreateEligibility(array $payload): array`

Create a new eligibility record.

**Throws:**

- `AuthException` - Token acquisition or authentication failed
- `OracleException` - API request failed or returned error
- `ResponseParsingException` - Response parsing failed

#### `oracleAddInvoice(array $payload): array`

Add invoice information to an existing eligibility.

**Throws:**

- `AuthException` - Token acquisition or authentication failed
- `OracleException` - API request failed or returned error
- `ResponseParsingException` - Response parsing failed

#### `oracleCancelEligibility(array $payload): array`

Cancel an existing eligibility record.

**Throws:**

- `AuthException` - Token acquisition or authentication failed
- `OracleException` - API request failed or returned error
- `ResponseParsingException` - Response parsing failed

### AdvanceCare SOAP API (Legacy)

All SOAP operations require `username` and `password` fields in the payload.

#### `performPharmaAct(array $payload): array`

Submit a pharmaceutical claim via SOAP.

**Throws:**

- `SoapException` - SOAP request failed or SOAP fault returned
- `InsuranceApiException` - Response mapping failed

#### `addInvoice(array $payload): array`

Add invoice information via SOAP.

**Throws:**

- `SoapException` - SOAP request failed or SOAP fault returned
- `InsuranceApiException` - Response mapping failed

#### `cancelEligibility(array $payload): array`

Cancel an eligibility record via SOAP.

**Throws:**

- `SoapException` - SOAP request failed or SOAP fault returned
- `InsuranceApiException` - Response mapping failed

## Exception Hierarchy

The library throws specific exceptions to help with error handling:

```

Exception (PHP native)
├── AdvClientAPI\Exceptions\InsuranceApiException (base for library exceptions)
│   ├── AuthException - Authentication/token issues
│   ├── SoapException - SOAP-specific failures
│   ├── OracleException - Oracle REST API failures
│   ├── ResponseParsingException - Response mapping failures
│   ├── ConfigException - Configuration errors
│   └── ...
```

**Note:** All library exceptions extend `InsuranceApiException`, allowing you to catch all library-specific errors with a single catch block.

## Configuration

```php
// Production (default)
$client = new AdvClient();

// Testing environment
$client = AdvClient::testInstance();
```

## Error Handling

All library exceptions extend `InsuranceApiException`. Handle specific exceptions for different error types:

```php
use AdvClientAPI\Exceptions\{
    InsuranceApiException,
    AuthException,
    SoapException,
    OracleException,
    ResponseParsingException,
    ConfigException
};

$client = new AdvClient();

try {
    $response = $client->oraclePerformPharmaAct($payload);
} catch (AuthException $e) {
    // Token acquisition failed - check credentials and OAuth2 endpoint
    echo "Authentication Error: " . $e->getMessage();
    
} catch (OracleException $e) {
    // Oracle REST API returned error or request failed
    echo "API Error: " . $e->getMessage();
    // Access error details: $e->getStatusCode(), $e->getResponseBody()
    
} catch (SoapException $e) {
    // SOAP request failed
    echo "SOAP Error: " . $e->getMessage();
    // Access SOAP details: $e->getSoapAction(), $e->getSoapBody()
    
} catch (ResponseParsingException $e) {
    // Response body couldn't be parsed
    echo "Parsing Error: " . $e->getMessage();
    
} catch (ConfigException $e) {
    // Configuration is missing or invalid
    echo "Config Error: " . $e->getMessage();
    
} catch (InsuranceApiException $e) {
    // Catch any other library exception
    echo "General Error: " . $e->getMessage();
    
} catch (Exception $e) {
    // Catch non-library exceptions
    echo "Unexpected Error: " . $e->getMessage();
}
```

### Common Issues

**OracleException "Redirect to login form"**

- Symptom: Response contains HTML instead of JSON
- Cause: Missing or invalid Bearer token in Authorization header
- Solution: Verify OAuth2 credentials (clientId, clientSecret) and token endpoint

**AuthException with HTTP 401/403**

- Symptom: Token acquisition fails
- Cause: Invalid credentials or insufficient scope
- Solution: Check OAuth2 credentials match provider configuration

**SoapException "SOAP Fault"**

- Symptom: Valid SOAP request but provider returns fault
- Cause: Invalid payload data or business logic error
- Solution: Verify all required fields in payload data

## Complete Example - PharmaAct Submission

```php
use AdvClientAPI\Core\AdvClient;
use AdvClientAPI\Exceptions\{AuthException, OracleException, ResponseParsingException};

// Initialize client
$client = AdvClient::testInstance();

// Prepare pharmaceutical claim request
$payload = [
    "auth" => [
        "clientId" => "your_oauth2_client_id",
        "clientSecret" => "your_oauth2_client_secret",
        "providerId" => "your_provider_id",
        "scope" => "https://adva-test-ohi.oracleindustry.com/test/urn::ohi-components-apis"
    ],
    "requestData" => [
        "payerCode" => "VIV",
        "insuranceType" => "S",
        "userName" => "provider_user",
        "memberCode" => "99999993000202",
        "localCode" => "AO5000078271-2",
        "locationType" => "FARMA",
        "memberPhoneNo" => "925334548",
        "emergency" => false,
        
        // Diagnosis information
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
        
        // Medical procedures/services
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

try {
    // Submit claim
    $response = $client->oraclePerformPharmaAct($payload);
    
    // Check result
    if ($response['result'] === 'success') {
        $claimId = $response['payload']['eventpayload']['claim'][0]['code'];
        echo "✓ Claim submitted successfully\n";
        echo "Claim ID: " . $claimId . "\n";
        
        // Access claim details
        $claim = $response['payload']['eventpayload']['claim'][0];
        echo "Status: " . ($claim['approvalStatus'] ?? 'Pending') . "\n";
        echo "Amount: " . $claim['approvedAmount'] ?? 'Not Approved' . "\n";
    } else {
        echo "✗ Claim submission failed\n";
        echo "Result: " . $response['result'] . "\n";
    }
    
} catch (AuthException $e) {
    echo "✗ Authentication Failed\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Check your OAuth2 credentials\n";
    
} catch (OracleException $e) {
    echo "✗ API Request Failed\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "HTTP Status: " . $e->getStatusCode() . "\n";
    
} catch (ResponseParsingException $e) {
    echo "✗ Response Parsing Failed\n";
    echo "Message: " . $e->getMessage() . "\n";
    
} catch (Exception $e) {
    echo "✗ Unexpected Error\n";
    echo "Message: " . $e->getMessage() . "\n";
}
```

### Response Structure

On success, responses follow this structure:

```php
[
    'result' => 'success',           // or 'error'
    'payload' => [
        'eventpayload' => [
            'claim' => [
                [
                    'code' => 'CLM123456',
                    'approvalStatus' => 'APPROVED',
                    'approvedAmount' => '5000',
                    'valueReference' => 'REF123'
                ]
            ],
            'diagnostics' => [
                [
                    'code' => 'B50',
                    'sequence' => 1,
                    'status' => 'ACCEPTED'
                ]
            ],
            'lineItem' => [
                [
                    'sequence' => 1,
                    'code' => 'P-0010325',
                    'status' => 'APPROVED',
                    'approvedUnits' => 1,
                    'approvedAmount' => '5000'
                ]
            ]
        ]
    ]
]
```

## SOAP API Examples

### AdvanceCare PharmaAct Submission

```php
use AdvClientAPI\Core\AdvClient;
use AdvClientAPI\Exceptions\{SoapException, InsuranceApiException};

$client = new AdvClient();

$payload = [
    "username" => "soap_user",
    "password" => "soap_password",
    "buID" => "ESA",
    "currencyCode" => "AOA",
    "dos" => "2026-03-05",
    "memID" => "MEMBER123",
    "practiceSeq" => 12345,
    "providerID" => "PROVIDER123",
    "created" => "2026-03-05T10:00:00Z",
    "pharmaServiceValuesList" => [
        [
            "amtClaimed" => "123.45",
            "procCode" => "PROC1",
            "iva" => "12.35",
            "unit" => 1
        ]
    ]
];

try {
    $response = $client->performPharmaAct($payload);
    echo "Eligibility ID: " . $response['eligibility_id'] . "\n";
    
} catch (SoapException $e) {
    echo "SOAP Error: " . $e->getMessage() . "\n";
    // Check $e->getSoapAction(), $e->getStatusCode()
    
} catch (InsuranceApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
```

**Throws:**

- `SoapException` - SOAP request failed or SOAP fault returned
- `InsuranceApiException` - Response validation failed

### AdvanceCare Add Invoice

```php
try {
    $response = $client->addInvoice([
        "username" => "soap_user",
        "password" => "soap_password",
        "eligibilityNbr" => 123456789,
        "memClinicId" => "INVOICE-123",
        "userId" => "testuser",
        "created" => "2026-03-05T10:00:00Z"
    ]);
    
} catch (SoapException $e) {
    echo "SOAP Error: " . $e->getMessage();
} catch (InsuranceApiException $e) {
    echo "API Error: " . $e->getMessage();
}
```

**Throws:**

- `SoapException` - SOAP request failed or SOAP fault returned
- `InsuranceApiException` - Response validation failed

### AdvanceCare Cancel Eligibility

```php
try {
    $response = $client->cancelEligibility([
        "username" => "soap_user",
        "password" => "soap_password",
        "eligibilityNbr" => 123456789,
        "created" => "2026-03-05T10:00:00Z"
    ]);
    
} catch (SoapException $e) {
    echo "SOAP Error: " . $e->getMessage();
} catch (InsuranceApiException $e) {
    echo "API Error: " . $e->getMessage();
}
```

**Throws:**

- `SoapException` - SOAP request failed or SOAP fault returned
- `InsuranceApiException` - Response validation failed

## License

**MIT License** - Permissive open-source license allowing commercial and private use.

## Troubleshooting Guide

### AuthException: "Token endpoint returned HTTP 401"

**Cause:** Invalid OAuth2 credentials  
**Solution:** Verify clientId and clientSecret are correct for your environment (test vs production)

### OracleException: "Redirect to login form" or HTML Response

**Cause:** Authorization header missing Bearer prefix or invalid token  
**Solution:** This is handled automatically by the library. If still occurring, check token scope matches provider requirements

### SoapException: "SOAP Fault in response"

**Cause:** Invalid data in SOAP request payload  
**Solution:** Verify all required fields are present and valid. Check SOAP action for typos

### ResponseParsingException: "Invalid JSON response"

**Cause:** API returned malformed JSON or unexpected format  
**Solution:** This may indicate the API changed its response format. Contact support with full response body

### ConfigException: "Missing configuration"

**Cause:** Required environment variables not set (for production use)  
**Solution:** Verify all required config values are in environment or passed to Config constructor

## API Reference

### Request Authentication (Oracle REST)

All Oracle REST operations require authentication in the request:

```php
"auth" => [
    "clientId" => "...",           // OAuth2 client ID
    "clientSecret" => "...",       // OAuth2 client secret  
    "providerId" => "...",         // Provider ID for endpoint
    "scope" => "..."               // (Optional) OAuth2 scope override
]
```

**Note:** The library automatically acquires and caches tokens. You do not need to manage token lifecycle.

### Request Authentication (AdvanceCare SOAP)

SOAP operations require credentials in each request:

```php
"username" => "...",              // SOAP username
"password" => "...",              // SOAP password
"created" => "2026-03-05T..."     // ISO 8601 timestamp
```

## Support

Email: <simtek2022@gmail.com>

For issue reports, include:

- Exception type and message
- Request payload (without credentials)
- Response body if available
- Environment (test or production)

---

**v1.0.0** | Maintained by SIMTECH, LDA
