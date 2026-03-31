<?php

declare(strict_types=1);

namespace AdvClientAPI\Tests;

use PHPUnit\Framework\TestCase;
use AdvClientAPI\Core\AdvClient;
use AdvClientAPI\Utilities\DateFormatter;
use AdvClientAPI\Utilities\XmlParser;
use AdvClientAPI\Utilities\RetryPolicy;
use AdvClientAPI\Auth\InMemoryTokenCache;
use AdvClientAPI\Mappers\PharmaActResponseMapper;
use AdvClientAPI\Mappers\OracleResponseMapper;
use function PHPUnit\Framework\assertEquals;

/**
 * Core Library Tests
 * Tests main library functionality including client initialization, configuration, and utilities
 */
class CoreLibraryTest extends TestCase
{
    /**
     * Test client initialization with default configuration
     */
    public function testClientInitializationWithDefaultConfig(): void
    {
        $client = new AdvClient();
        $this->assertInstanceOf(AdvClient::class, $client);
    }

    /**
     * Test static factory method
     */
    // public function testStaticFactoryMethod(): void
    // {
    //     $client = InsuranceApiClient::create([
    //         'advancecare_env' => 'PROD',
    //     ]);

    //     $this->assertInstanceOf(InsuranceApiClient::class, $client);
    //     $configArray = $client->getConfigArray();
    //     $this->assertEquals('PROD', $configArray['advancecare_env']);
    // }

    public function testDateFormatter(): void
    {
        $formatted = DateFormatter::formatDosDate('20-02-2024');
        $this->assertEquals('2024-02-20', $formatted);

        $iso8601 = DateFormatter::getCurrentIso8601($formatted);

        $this->assertStringContainsString('T', $iso8601);
        $this->assertStringContainsString('Z', $iso8601);
    }

    /**
     * Test ISO8601 formatting
     */
    public function testIso8601DateFormatting(): void
    {
        $timestamp = strtotime('2024-02-20 10:30:00');
        $iso8601 = DateFormatter::formatTimestamp($timestamp);

        $this->assertStringContainsString('2024-02-20', $iso8601);
        $this->assertStringContainsString('T', $iso8601);
    }

    /**
     * Test XML parsing
     */
    public function testXmlParsing(): void
    {
        $xml = '<?xml version="1.0"?><root><value>test</value></root>';
        $parsed = XmlParser::parse($xml);

        $this->assertInstanceOf(\SimpleXMLElement::class, $parsed);
        $this->assertEquals('test', (string)$parsed->value);
    }

    /**
     * Test XML parsing with complex structure
     */
    public function testXmlParsingComplex(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<response>
    <status>SUCCESS</status>
    <data>
        <id>123</id>
        <name>Test</name>
    </data>
</response>
XML;

        $parsed = XmlParser::parse($xml);

        $this->assertEquals('SUCCESS', (string)$parsed->status);
        $this->assertEquals('123', (string)$parsed->data->id);
        $this->assertEquals('Test', (string)$parsed->data->name);
    }

    /**
     * Test retry policy configuration
     */
    public function testRetryPolicyConfiguration(): void
    {
        $policy = new RetryPolicy(3, 2.0, 100);
        $config = $policy->getConfig();

        $this->assertEquals(3, $config['maxRetries']);
        $this->assertEquals(2.0, $config['backoffFactor']);
        $this->assertEquals(100, $config['initialDelayMs']);
    }

    /**
     * Test in-memory token cache
     */
    public function testInMemoryTokenCache(): void
    {
        $cache = new InMemoryTokenCache();

        // Test set and get
        $cache->set('test-key', 'test-token', time() + 3600);
        $cached = $cache->get('test-key');

        $this->assertNotNull($cached);
        $this->assertEquals('test-token', $cached['accessToken']);
    }

    /**
     * Test token cache expiration
     */
    public function testTokenCacheExpiration(): void
    {
        $cache = new InMemoryTokenCache();

        // Set token with past expiration
        $cache->set('expired-key', 'old-token', time() - 1);
        $cached = $cache->get('expired-key');

        $this->assertNull($cached);
    }

    /**
     * Test token cache deletion
     */
    public function testTokenCacheDeletion(): void
    {
        $cache = new InMemoryTokenCache();

        $cache->set('test-key', 'test-token', time() + 3600);
        $this->assertNotNull($cache->get('test-key'));

        $cache->delete('test-key');
        $this->assertNull($cache->get('test-key'));
    }

    /**
     * Test PharmaAct response mapping
     */
    public function testPharmaActResponseMapping(): void
    {
        $soapXml = <<<'XML'
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
  <SOAP-ENV:Header/>
  <SOAP-ENV:Body>
    <ns3:performPharmaActResponse xmlns:ns3="http://pt.advancecare.awsp.eligibilityAO">
      <result>
        <returnCode>0</returnCode>
        <totalResults>1</totalResults>
        <results>
          <assistantName xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>
          <buName>FOR</buName>
          <catCode>2</catCode>
          <catName>Ambulatório</catName>
          <clinicName>Bairro Comercial Rua ex. Câmara Leme</clinicName>
          <currencyCode>AOA</currencyCode>
          <dos>2021-03-10T16:11:36.000Z</dos>
          <elegibilityNbr>1117295483</elegibilityNbr>
          <eligibilityServicesValuesReturn>
            <amtClaimed>16238.5</amtClaimed>
            <anatomicRegion xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>
            <incidence xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>
            <laterality xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>
            <load xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>
            <procCode>7355586</procCode>
            <serviceCode>7300</serviceCode>
            <unit>30.0</unit>
            <aggravationRate>0</aggravationRate>
            <amtCopay>1623.85</amtCopay>
            <amtPaid>14614.65</amtPaid>
            <totalIva>1794.78</totalIva>
          </eligibilityServicesValuesReturn>
          <icd9Codes>
            <diagCode>E947</diagCode>
          </icd9Codes>
          <memFullName>Pessoa Ficticia</memFullName>
          <memID>100</memID>
          <payeeId>5401127251</payeeId>
          <payeeToId xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>
          <practiceSeq>3</practiceSeq>
          <providerID>5401127251</providerID>
          <providerName>FARMA</providerName>
          <specialtyDesc>Farmácia</specialtyDesc>
          <totalAmtCopay>1623.85</totalAmtCopay>
          <totalAmtPaid>14614.65</totalAmtPaid>
          <totalPagoComIva>1794.78</totalPagoComIva>
          <userId>ACA9999</userId>
          <memPhone xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>
          <memEmail xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>
        </results>
      </result>
    </ns3:performPharmaActResponse>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>

XML;



        $mapper = new PharmaActResponseMapper($soapXml);
        $result = $mapper->map();
        // var_dump($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(1117295483, $result['results']['elegibilityNbr']);
        $this->assertEquals("Pessoa Ficticia", $result['results']['memFullName']);
    }

    /**
     * Test Oracle REST response mapping
     */
    public function testOracleResponseMapping(): void
    {
        $jsonResponse = '{
  "payload": {
    "eventpayload": {
      "claim": [
        {
          "code": "48340334901",
          "status": "APPROVED",
          "payerCode": "VIV",
          "lineOfBusiness": "S",
          "memberCode": "123451501",
          "memberName": "PEDRO PEVIDE",
          "localCode": "AO5000000099-1",
          "paymentReceiver": "AO5000000099",
          "locationType": "FARMA",
          "emergency": false,
          "totalRequestedAmount": {
            "value": 25000,
            "currency": "AOA"
          },
          "totalAllowedAmount": {
            "value": "25000",
            "currency": "AOA"
          },
          "totalMemberCoPayAmount": {
            "value": 0.00,
            "currency": "AOA"
          },
          "totalNotCoveredAmount": {
            "value": 0,
            "currency": "AOA"
          },
          "totalMemberShareAmount": {
            "value": 0,
            "currency": "AOA"
          },
          "totalApprovedAmount": {
            "value": 25000.00,
            "currency": "AOA"
          },
          "totalIvaApprovedAmount": {
            "value": 0,
            "currency": "AOA"
          },
          "totalPaymentAmount": {
            "value": 25000.00,
            "currency": "AOA"
          },
          "externalClaimsdata": 63420955622,
          "claimDiagnosisList": [
            {
              "diagnosisCode": "B50",
              "sequence": 1,
              "diagnosisType": "P",
              "diagnosisDate": "2026-02-12T23:00:00+0000",
              "symptomsDate": "2026-02-12T23:00:00+0000",
              "classification": "CID10"
            }
          ],
          "claimMessageList": [],
          "claimPendReasonList": [],
          "claimLineList": [
            {
              "sequence": "1",
              "medicalActCode": "P-0025825",
              "serviceType": "4",
              "startDate": "2026-02-13",
              "endDate": "2026-02-13",
              "status": "APPROVED",
              "requestedUnits": 1,
              "requestedAmount": {
                "value": 12500,
                "currency": "AOA"
              },
              "allowedAmount": {
                "value": 12500,
                "currency": "AOA"
              },
              "memberCoPayAmount": {
                "value": 0.00,
                "currency": "AOA"
              },
              "notCoveredAmount": {
                "value": 0.00,
                "currency": "AOA"
              },
              "memberShareAmount": {
                "value": 0.00,
                "currency": "AOA"
              },
              "approvedAmount": {
                "value": 12500.00,
                "currency": "AOA"
              },
              "ivaApprovedAmount": {
                "value": 0,
                "currency": "AOA"
              },
              "paymentAmount": {
                "value": 12500.00,
                "currency": "AOA"
              },
              "claimLineMessageList": [],
              "claimLinePendReasonList": [],
              "serviceComponents": [],
              "lineNotes": "[]"
            }
          ],
          "advPortalLink": "https://adv-vbcs-dev-vb-frvxufgkss8j-fr.builder.ocp.oraclecloud.com/ic/builder/rt/provider/live/webApps/advportal/?page=shell&shell=authorization-eligibility&authorization-eligibility=authorization-eligibility-detail&id=48340334901"
        }
      ],
      "claimCode": "48340334901",
      "code": "48340334901",
      "event": "RES_FINALIZED_RSP_PWS",
      "level": "C",
      "oigExchangeId": "191160938",
      "timestamp": {
        "value": "2026-02-13T10:09:56.064+01:00"
      },
      "topic": "ProviderWebservice - Reservation Finalized"
    },
    "status": "SUCCESS"
  }
}';

        $mapper = new OracleResponseMapper($jsonResponse);
        $result = $mapper->map();

        // $this->assertTrue($result['success']);
        // $this->assertEquals('SUCCESS', $result['status']);
        // $this->assertEquals('48340334901', $result['code']);
        // $this->assertCount(1, $result['claims']);
        
        // $claim = $result['claims'][0];
        // $this->assertEquals('APPROVED', $claim['status']);
        // $this->assertEquals('PEDRO PEVIDE', $claim['memberName']);
        // $this->assertEquals(25000.00, $claim['amounts']['totalApprovedAmount']);
        // $this->assertCount(1, $claim['diagnoses']);
        // $this->assertEquals('B50', $claim['diagnoses'][0]['code']);
        // $this->assertCount(1, $claim['lineItems']);
        // $this->assertEquals('P-0025825', $claim['lineItems'][0]['medicalActCode']);
    }


    /**
     * Test response mapper error handling
     */
    public function testResponseMapperErrorHandling(): void
    {
        $errorXml = <<<'XML'
    <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
        <SOAP-ENV:Header />
        <SOAP-ENV:Body>
            <ns3:performPharmaActResponse xmlns:ns3="http://pt.advancecare.awsp.eligibilityAO">
                <result>
                    <messages>Contrato para o providerId: AO5000099991, practiceSeq: 3 nï¿½o
                        encontrado.</messages>
                    <messages>Para qualquer esclarecimento adicional contacte a AdvanceCare através
                        do número de telefone 226434124 ou 923120261.</messages>
                    <returnCode>1</returnCode>
                    <totalResults>0</totalResults>
                    <results xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true" />
                </result>
            </ns3:performPharmaActResponse>
        </SOAP-ENV:Body>
    </SOAP-ENV:Envelope> 
XML;

        $mapper = new PharmaActResponseMapper($errorXml);
    
      $result =   $mapper->map();
    
      $this->assertFalse($result['success']);
              assertEquals(1, $result['returnCode']);
              assertEquals(0, $result['totalResults']);
              
    }
}
