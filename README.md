# ADV Insurance API PHP Library

A robust PHP library for integrating with ADV Insurance web services.

## Installation

```bash
composer require simtech/adv-library

```

// If you have any trouble with the project requiring a stable version please run:

```bash

composer require simtech/adv-library dev-main

```

## AdvanceCare Example (SOAP)

This example shows how to submit a pharmaceutical claim using the AdvanceCare SOAP API and handle potential errors.

```php
use AdvClientAPI\Core\AdvClient;
use AdvClientAPI\Exceptions\SoapException;
use AdvClientAPI\Exceptions\InsuranceApiException;

// Create an instance of the AdvClient with Production Configuration
//
$client = new AdvClient();

//Create the payload with the required fields
$payload = [
    "username" => "your_soap_username",
    "password" => "your_soap_password",
    "buID" => "ESA",
    "currencyCode" => "AOA",
    "dos" => "2026-03-05",
    "memID" => "900",
    "practiceSeq" => 1,
    "providerID" => "PROVIDER123",
    "created" => "2026-03-05T10:00:00Z",
   [
                "buID" => "ESA",
                "currencyCode" => "AOA",
                "dos" => date('c'), // Generates ISO 8601 format
                "memID" => "900",
                "practiceSeq" => 1,
                "providerID" => "5999999999",
                "username" =>   "PPP1234",
                "password" => "sssssss",
                "pharmaServiceValuesList" => [
                    [
                        "amtClaimed" => "263.16",
                        "procCode" => "99999990",
                        "iva" => "2.00",
                        "unit" => 4
                    ],
                    [
                        "amtClaimed" => "441.18",
                        "procCode" => "99999991",
                        "iva" => "2.00",
                        "unit" => 1
                    ]
                ],
             
            ];
];

//Its recommended to handle the exceptions wtih try-catch blocks
try {
    $response = $client->performPharmaAct($payload);
    echo "Success! Eligibility ID: " . $response['eligibility_id'] . "\n";
} catch (SoapException $e) {
    // Handle SOAP-specific errors (e.g., connection issues, SOAP faults)
    echo "SOAP Error: " . $e->getMessage() . "\n";
    // For more details, you can inspect the exception:
    // $e->getSoapAction(), $e->getSoapBody()
} catch (InsuranceApiException $e) {
    // Handle other general API errors (e.g., response mapping issues)
    echo "API Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    // Handle any other unexpected errors
    echo "An unexpected error occurred: " . $e->getMessage() . "\n";
}

```

## Oracle AdvanceCare Example (REST)

This example shows how to submit a pharmaceutical claim using the Oracle REST API, which includes automatic OAuth2 token management.

```php
use AdvClientAPI\Core\AdvClient;
use AdvClientAPI\Exceptions\AuthException;
use AdvClientAPI\Exceptions\OracleException;
use AdvClientAPI\Exceptions\ResponseParsingException;

// This is a production Instance with production configuration, use AdvClient::TestInstance() for testing
$client = new AdvClient();

$payload =[
                "auth" => [
                    "clientId" => "YOUR_OAUTH_CLIENT_ID",
                    "clientSecret" => "YOUR_OAUTH_CLIENT_SECRET",
                    "providerId" => "YOUR_PROVIDER_ID"
                ],
                "requestData" => [
                    "payerCode" => "VIV",
                    "insuranceType" => "S",
                    "userName" => "Provideruserone",
                    "memberCode" => "11111111111111",
                    "localCode" => "AO5999999999-1",

                    "locationType" => "FARMA",

                    "memberPhoneNo" => "921000000",
                    "emergency" => "false",

                    "claimDiagnosisList" => [
                        [
                            "sequence" => 1,
                            "diagnosisType" => "P",
                            "diagnosisDate" => "2026-02-13",
                            "symptomsDate" => "2026-02-13",
                            "diagnosisCode" => "B50",
                            "classification" => "CID10"
                        ],

                    ],
                    "claimLineList" => [
                        [
                            "sequence" => "1",
                            "medicalActCode" => "P-0010325",
                            "startDate" => "2026-02-13",
                            "endDate" => "2026-02-13",
                            "requestedUnits" => 1,
                            "requestedAmount" => ["value" => "5325", "currency" => "AOA"],
                        ],
                        [
                            "sequence" => "2",
                            "medicalActCode" => "P-0046625",
                            "startDate" => "2026-02-13",
                            "endDate" => "2026-02-13",
                            "requestedUnits" => 1,
                            "requestedAmount" =>
                            [
                                "value" => "5325",
                                "currency" => "AOA"

                            ]
                        ]
                    ]
                ]
            ];

try {
    $response = $client->($payload);
    echo "Success! Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (AuthException $e) {
    // Handle authentication errors (e.g., invalid credentials, token failure)
    echo "Authentication Error: " . $e->getMessage() . "\n";
} catch (OracleException $e) {
    // Handle Oracle API errors (e.g., bad request, server errors)
    echo "Oracle API Error: " . $e->getMessage() . "\n";
    // For more details, you can inspect the exception:
    // $e->getStatusCode(), $e->getResponseBody()
} catch (ResponseParsingException $e) {
    // Handle issues with parsing the API response
    echo "Response Parsing Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    // Handle any other unexpected errors
    echo "An unexpected error occurred: " . $e->getMessage() . "\n";
}
```

## PharmaAct Cancellation

This is an Example of how to reques the cancellation of an existing Elibility

```php

use AdvClientAPI\Core\AdvClient;


        try {
            $providerId = 'Your_providerID';
            $clientId = 'YOUR_CLIENT_ID';
            $clientScret = 'YOUR_CLIENT_SECRET';

            // Use AdvClient() for production configuration
            $client = AdvClient::testInstance();
          
            $payload = [
                "auth" =>
                [
                    "clientId" => $clientId,
                    "clientSecret" => $clientScret,
                    "providerId" => $providerId,
                    
                ],
                "requestData" => [
                    "payerCode" => "VIV",
                    "localCode" => "AO5999999999-2",
                    "memberCode" => "11111111111111",
                    "providerReference" => "",
                    "claimCode" => "49324670577",
                    "cancellationReasonCode" => "CAN_DUP",
                    "userName" => "Provideruserone"
                ]

            ];
            $result = $client->oracleCancelEligibility($payload);
            // var_dump($result);
            
        } catch (Exception $e) {
            print($e->getMessage());
        }


```

## Types of Errors (Exceptions)

The library uses a set of custom exceptions to help you identify the source of an error quickly. All library exceptions extend `AdvClientAPI\Exceptions\InsuranceApiException`.

- `AuthException`: Thrown when OAuth2 authentication fails. This could be due to invalid credentials (`clientId`, `clientSecret`), an invalid scope, or issues with the token endpoint.

- `OracleException`: Thrown when the Oracle REST API returns an error. This usually includes an HTTP status code and a response body with more details.

- `SoapException`: Thrown for errors related to the AdvanceCare SOAP API, such as connection failures or SOAP faults returned by the server.

- `ResponseParsingException`: Thrown if the library fails to parse the API's response (e.g., invalid JSON or XML).

- `ConfigException`: Thrown if the library is misconfigured (e.g., a required URL or setting is missing).

- `InsuranceApiException`: This is the base exception. You can use it as a catch-all for any error originating from this library.

## License

MIT License
