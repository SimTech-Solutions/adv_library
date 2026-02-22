<?php

declare(strict_types=1);

namespace AdvClientAPI\Utilities;

use SimpleXMLElement;
use AdvClientAPI\Exceptions\ResponseParsingException;

/**
 * XML parsing utilities
 */
class XmlParser
{
    /**
     * Parse XML string into SimpleXMLElement
     *
     * @param string $xml
     * @return SimpleXMLElement
     * @throws ResponseParsingException
     */
    public static function parse(string $xml): SimpleXMLElement
    {
        try {
            $previous = libxml_use_internal_errors(true);
            libxml_clear_errors();

            $element = simplexml_load_string($xml);

            if ($element === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                libxml_use_internal_errors($previous);

                $errorMsg = !empty($errors) ? $errors[0]->message : 'Unknown XML parse error';
                throw new ResponseParsingException(
                    "Failed to parse XML: {$errorMsg}",
                    $xml,
                    'XML'
                );
            }

            libxml_use_internal_errors($previous);
            return $element;
        } catch (ResponseParsingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ResponseParsingException(
                "XML parsing error: {$e->getMessage()}",
                $xml,
                'XML'
            );
        }
    }

    /**
     * Extract string value from XML element
     *
     * @param SimpleXMLElement $element
     * @param string $path XPath expression
     * @param string|null $default Default value if not found
     * @return string|null
     */
    public static function getString(
        SimpleXMLElement $element,
        string $path,
        ?string $default = null
    ): ?string {
        $results = $element->xpath($path);
        if (!empty($results)) {
            return (string)$results[0];
        }
        return $default;
    }

    /**
     * Extract all text from an XML element and its children
     *
     * @param SimpleXMLElement $element
     * @return string
     */
    public static function getInnerXml(SimpleXMLElement $element): string
    {
        return (string)$element;
    }

    /**
     * Convert SimpleXMLElement to array recursively
     *
     * @param SimpleXMLElement $xml
     * @return array<string, mixed>
     */
    public static function xmlToArray(SimpleXMLElement $xml): array
    {
        $result = [];

        foreach ($xml->children() as $key => $child) {
            $childArray = self::xmlToArray($child);

            if (isset($result[$key])) {
                // Convert to array if multiple same elements
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $childArray;
            } else {
                $result[$key] = $childArray;
            }
        }

        // if (empty($result)) {
        //     return (string)$xml;
        // }

        return $result;
    }

    /**
     * Find text value anywhere in the XML document
     * Searches recursively through all descendants
     *
     * @param SimpleXMLElement $element
     * @param string $tag Tag name to find
     * @return string|null
     */
    public static function findTextAnywhere(SimpleXMLElement $element, string $tag): ?string
    {
        $results = $element->xpath(".//{$tag}");
        if (!empty($results)) {
            return (string)$results[0];
        }
        return null;
    }

    /**
     * Find all matching elements
     *
     * @param SimpleXMLElement $element
     * @param string $path XPath expression
     * @return array<SimpleXMLElement>
     */
    public static function findAll(SimpleXMLElement $element, string $path): array
    {
        $results = $element->xpath($path);
        return is_array($results) ? $results : [];
    }

    /**
     * Find a single element by XPath
     *
     * @param SimpleXMLElement $element
     * @param string $path XPath expression
     * @return SimpleXMLElement|null
     */
    public static function findElement(SimpleXMLElement $element, string $path): ?SimpleXMLElement
    {
        $results = $element->xpath($path);
        return !empty($results) ? $results[0] : null;
    }
}
