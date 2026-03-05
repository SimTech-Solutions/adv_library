<?php
declare(strict_types=1);

namespace AdvClientAPI\Tests;


use PHPUnit\Framework\TestCase;
use AdvClientAPI\Templates\TemplateRenderer;
use AdvClientAPI\Utilities\DateFormatter;

/**
 * Template Rendering Tests
 * Verifies that all SOAP templates render correctly with sample data
 */
class TemplateRenderingTest extends TestCase
{
    private TemplateRenderer $renderer;
    private array $securityData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $templatesPath = dirname(__DIR__) . '/src/Templates';
        $this->renderer = new TemplateRenderer($templatesPath);

        // Setup common security data
        
        $this->securityData = [
            'username' => 'testuser@example.com',
            'password' => 'testpass123',
            'created' => DateFormatter::getCurrentIso8601('20-02-2024'),
            'expires' => DateFormatter::formatTimestamp(time() + 3600),
            'timestamp_id' => 'test-timestamp-123',
        ];
    }

    /**
     * Test pharma_act.xml.j2 template
     * Verifies: variable substitution, for loops, conditionals
     */
    public function testPharmaActTemplate(): void
    {
        $data = array_merge($this->securityData, [
            'data' => [
                'buID' => 'BU001',
                'currencyCode' => 'USD',
                'dos' => '2024-02-20',
                'memID' => 'MEM001',
                'practiceSeq' => '12345',
                'providerID' => 'PROV001',
                'memPhone' => '555-1234',
                'memEmail' => 'member@example.com',
                'pharmaServiceValuesList' => [
                    [
                        'amtClaimed' => '100.50',
                        'procCode' => 'PROC001',
                        'iva' => '21.00',
                        'unit' => '1',
                    ],
                    [
                        'amtClaimed' => '50.25',
                        'procCode' => 'PROC002',
                        'iva' => '10.50',
                        'unit' => '2',
                    ],
                ],
            ],
        ]);

        $output = $this->renderer->render('advancecare/pharma_act.xml.j2', $data);

        // Verify XML is well-formed
        $this->assertValidXml($output);

        // Verify security elements
        $this->assertStringContainsString('testuser@example.com', $output, 'Username should be in output');
        $this->assertStringContainsString('testpass123', $output, 'Password should be in output');

        // Verify variable substitution
        $this->assertStringContainsString('<buID>BU001</buID>', $output, 'buID should be substituted');
        $this->assertStringContainsString('<currencyCode>USD</currencyCode>', $output, 'currencyCode should be substituted');
        $this->assertStringContainsString('<dos>2024-02-20</dos>', $output, 'dos should be substituted');
        $this->assertStringContainsString('<memID>MEM001</memID>', $output, 'memID should be substituted');

        // Verify for loop rendering (should have 2 pharmaServiceValues blocks)
        $count = substr_count($output, '<pharmaServiceValues>');
        $this->assertEquals(2, $count, 'Should have 2 pharmaServiceValues blocks');
        $this->assertStringContainsString('<procCode>PROC001</procCode>', $output, 'First proc code should be in output');
        $this->assertStringContainsString('<procCode>PROC002</procCode>', $output, 'Second proc code should be in output');

        // Verify optional fields are rendered
        $this->assertStringContainsString('<memPhone>555-1234</memPhone>', $output, 'memPhone should be rendered');
        $this->assertStringContainsString('<memEmail>member@example.com</memEmail>', $output, 'memEmail should be rendered');
    }

    /**
     * Test create_eligibility.xml.j2 template
     * Verifies: nested for loops, conditionals with .get() method
     */
    public function testCreateEligibilityTemplate(): void
    {
        $data = array_merge($this->securityData, [
            'data' => [
                'dos' => '2024-02-20',
                'memID' => 'MEM001',
                'buID' => 'BU001',
                'currencyCode' => 'USD',
                'practiceSeq' => '12345',
                'providerID' => 'PROV001',
                'specialtyNbr' => 'SPEC001',
                'icd9Codes' => [
                    ['diagCode' => 'ICD001'],
                    ['diagCode' => 'ICD002'],
                    ['diagCode' => 'ICD003'],
                ],
                'eligibilityServiceValues' => [
                    [
                        'amtClaimed' => '200.00',
                        'procCode' => 'PROC001',
                        'serviceCode' => 'SVC001',
                        'unit' => '1',
                    ],
                    [
                        'amtClaimed' => '150.00',
                        'procCode' => 'PROC002',
                        'serviceCode' => 'SVC002',
                        'unit' => '2',
                    ],
                ],
            ],
        ]);

        $output = $this->renderer->render('advancecare/create_eligibility.xml.j2', $data);

        // Verify XML is well-formed
        $this->assertValidXml($output);

        // Verify security elements
        $this->assertStringContainsString('testuser@example.com', $output, 'Username should be in output');
        $this->assertStringContainsString('testpass123', $output, 'Password should be in output');

        // Verify variable substitution
        $this->assertStringContainsString('<dos>2024-02-20</dos>', $output, 'dos should be substituted');
        $this->assertStringContainsString('<memID>MEM001</memID>', $output, 'memID should be substituted');
        $this->assertStringContainsString('<buID>BU001</buID>', $output, 'buID should be substituted');
        $this->assertStringContainsString('<specialtyNbr>SPEC001</specialtyNbr>', $output, 'specialtyNbr should be substituted');

        // Verify ICD9 for loop rendering (should have 3 icd9Codes blocks)
        $icd9Count = substr_count($output, '<icd9Codes>');
        $this->assertEquals(3, $icd9Count, 'Should have 3 icd9Codes blocks');
        $this->assertStringContainsString('<diagCode>ICD001</diagCode>', $output, 'First ICD code should be in output');
        $this->assertStringContainsString('<diagCode>ICD002</diagCode>', $output, 'Second ICD code should be in output');
        $this->assertStringContainsString('<diagCode>ICD003</diagCode>', $output, 'Third ICD code should be in output');

        // Verify eligibilityServiceValues for loop rendering (should have 2 entries)
        $svcCount = substr_count($output, '<eligibilityServiceValues>');
        $this->assertEquals(2, $svcCount, 'Should have 2 eligibilityServiceValues blocks');
        $this->assertStringContainsString('<procCode>PROC001</procCode>', $output, 'First service proc code should be in output');
        $this->assertStringContainsString('<procCode>PROC002</procCode>', $output, 'Second service proc code should be in output');
    }

    /**
     * Test add_invoice.xml.j2 template
     * Verifies: simple variable substitution
     */
    public function testAddInvoiceTemplate(): void
    {
        $data = array_merge($this->securityData, [
            'data' => [
                'eligibilityNbr' => 'ELG001',
                'memClinicId' => 'CLINIC001',
                'userId' => 'USER001',
            ],
        ]);

        $output = $this->renderer->render('advancecare/add_invoice.xml.j2', $data);

        // Verify XML is well-formed
        $this->assertValidXml($output);

        // Verify security elements
        $this->assertStringContainsString('testuser@example.com', $output, 'Username should be in output');
        $this->assertStringContainsString('testpass123', $output, 'Password should be in output');

        // Verify variable substitution
        $this->assertStringContainsString('<eligibilityNbr>ELG001</eligibilityNbr>', $output, 'eligibilityNbr should be substituted');
        $this->assertStringContainsString('<memClinicId>CLINIC001</memClinicId>', $output, 'memClinicId should be substituted');
        $this->assertStringContainsString('<userId>USER001</userId>', $output, 'userId should be substituted');

        // Verify the method name is correct
        $this->assertStringContainsString('addInvoiceNumberRequest', $output, 'Correct SOAP method name');
    }

    /**
     * Test cancel_eligibility.xml.j2 template
     * Verifies: simple variable substitution with correct field name
     */
    public function testCancelEligibilityTemplate(): void
    {
        $data = array_merge($this->securityData, [
            'data' => [
                'elegibilityNbr' => 'ELG001',
            ],
        ]);

        $output = $this->renderer->render('advancecare/cancel_eligibility.xml.j2', $data);

        // Verify XML is well-formed
        $this->assertValidXml($output);

        // Verify security elements
        $this->assertStringContainsString('testuser@example.com', $output, 'Username should be in output');
        $this->assertStringContainsString('testpass123', $output, 'Password should be in output');

        // Verify variable substitution (note: elegibilityNbr with one 'i')
        $this->assertStringContainsString('<elegibilityNbr>ELG001</elegibilityNbr>', $output, 'elegibilityNbr should be substituted');

        // Verify the method name is correct
        $this->assertStringContainsString('nullifyEligibilityOrSingleActRequest', $output, 'Correct SOAP method name');
    }

    /**
     * Test conditionals with missing optional fields
     * Ensures nil elements are generated when fields are absent
     */
    public function testConditionalWithMissingFields(): void
    {
        $data = array_merge($this->securityData, [
            'data' => [
                'buID' => 'BU001',
                'currencyCode' => 'USD',
                'dos' => '2024-02-20',
                'memID' => 'MEM001',
                'practiceSeq' => '12345',
                'providerID' => 'PROV001',
                // Note: memPhone and memEmail are missing
                'pharmaServiceValuesList' => [
                    [
                        'amtClaimed' => '100.50',
                        'procCode' => 'PROC001',
                        'iva' => '21.00',
                        'unit' => '1',
                    ],
                ],
            ],
        ]);

        $output = $this->renderer->render('advancecare/pharma_act.xml.j2', $data);

        // Verify XML is well-formed
        $this->assertValidXml($output);

        // Verify nil elements are present for missing optional fields
        $this->assertStringContainsString('xsi:nil="true"', $output, 'Expected xsi:nil=true for missing optional fields');
    }

    /**
     * Test nested loops with correct data path resolution
     */
    public function testNestedDataPathResolution(): void
    {
        $data = array_merge($this->securityData, [
            'data' => [
                'dos' => '2024-02-20',
                'memID' => 'MEM001',
                'buID' => 'BU001',
                'currencyCode' => 'USD',
                'practiceSeq' => '12345',
                'providerID' => 'PROV001',
                'specialtyNbr' => 'SPEC001',
                'icd9Codes' => [
                    [
                        'diagCode' => 'J45.901',  // Unspecified asthma
                    ],
                ],
                'eligibilityServiceValues' => [
                    [
                        'amtClaimed' => '500.00',
                        'procCode' => 'CONS001',
                        'serviceCode' => 'CONSULT',
                        'unit' => '1',
                    ],
                ],
            ],
        ]);

        $output = $this->renderer->render('advancecare/create_eligibility.xml.j2', $data);

        // Verify XML is well-formed
        $this->assertValidXml($output);

        // Verify nested path resolution (data.field)
        $this->assertStringContainsString('<diagCode>J45.901</diagCode>', $output, 'ICD code with dots should be preserved');
        $this->assertStringContainsString('<procCode>CONS001</procCode>', $output, 'Proc code should be resolved from nested array');
        $this->assertStringContainsString('<amtClaimed>500.00</amtClaimed>', $output, 'Amount should be resolved from nested array');
    }

    /**
     * Assert that string is valid XML
     *
     * @param string $xml
     */
    private function assertValidXml(string $xml): void
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $this->assertNotFalse($doc, 'XML should parse successfully');
        $this->assertEmpty($errors, 'XML should have no parsing errors');
    }
}