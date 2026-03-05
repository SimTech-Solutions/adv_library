<?php

declare(strict_types=1);

namespace AdvClientAPI\Mappers;

use AdvClientAPI\Contracts\ResponseMapperInterface;
use AdvClientAPI\Utilities\XmlParser;
use SimpleXMLElement;

/**
 * Base response mapper with common XML parsing logic
 */
abstract class BaseResponseMapper implements ResponseMapperInterface
{
    protected SimpleXMLElement $response;

    public function __construct(string $xmlContent)
    {
        $this->response = XmlParser::parse($xmlContent);
    }

    /**
     * Extract text from XML element by XPath
     *
     * @param SimpleXMLElement|null $element
     * @param string $tag Tag name to find
     * @return string|null
     */
    protected function findText(?SimpleXMLElement $element, string $tag): ?string
    {
        if ($element === null) {
            return null;
        }
        return XmlParser::getString($element, $tag);
    }

    /**
     * Extract text value anywhere in document
     *
     * @param string $tag
     * @return string|null
     */
    protected function findTextAnywhere(string $tag): ?string
    {
        return XmlParser::findTextAnywhere($this->response, $tag);
    }

    /**
     * Find a single element
     *
     * @param string $path XPath expression
     * @return SimpleXMLElement|null
     */
    protected function findElement(string $path): ?SimpleXMLElement
    {
        return XmlParser::findElement($this->response, $path);
    }

    /**
     * Find all matching elements
     *
     * @param SimpleXMLElement|null $element
     * @param string $path XPath expression
     * @return array<SimpleXMLElement>
     */
    protected function findAll(?SimpleXMLElement $element, string $path): array
    {
        if ($element === null) {
            return [];
        }
        return XmlParser::findAll($element, $path);
    }

    /**
     * Convert string to int with error handling
     *
     * @param string|null $value
     * @return int|null
     */
    protected function safeInt(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return (int)$value;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert string to float with error handling
     *
     * @param string|null $value
     * @return float|null
     */
    protected function safeFloat(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return (float)$value;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert string to decimal with error handling
     *
     * @param string|null $value
     * @return string|null Returns as string to preserve precision
     */
    protected function toDecimal(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            // Keep as string to preserve decimal precision
            return (string)$value;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract all error/info messages from SOAP response
     *
     * @return array<string>
     */
    protected function extractMessages(): array
    {
        $messages = [];
        $msgElements = XmlParser::findAll($this->response, './/messages');
        foreach ($msgElements as $elem) {
            $text = (string)$elem;
            if (!empty($text)) {
                $messages[] = $text;
            }
        }
        return $messages;
    }

    /**
     * Extract multiple values from XML
     *
     * @param array<string, string> $paths Key => XPath pairs
     * @return array<string, mixed>
     */
    protected function extractMultiple(array $paths): array
    {
        $result = [];
        foreach ($paths as $key => $path) {
            $result[$key] = XmlParser::getString($this->response, $path);
        }
        return $result;
    }
}
