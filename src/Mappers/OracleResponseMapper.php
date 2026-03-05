<?php

declare(strict_types=1);

namespace AdvClientAPI\Mappers;

use AdvClientAPI\Exceptions\ResponseParsingException;

/**
 * Maps Oracle REST API JSON responses to array format
 * Extracts claim details, line items, and diagnosis information
 */
class OracleResponseMapper implements \AdvClientAPI\Contracts\ResponseMapperInterface
{
    /** @var array<string, mixed> */
    private array $response;
    private String $jsonContent;


    /**
     * Constructor
     *
     * @param string $jsonContent JSON response content
     * @throws ResponseParsingException
     */
    public function __construct(String $json)

    {
        $this->jsonContent = $json;
        
    }

    /**
     * Map Oracle REST response to array
     *
     * @return array<string, mixed>
     * @throws ResponseParsingException
     */
    public function map(): array
    {
        try {
          
        $content = json_decode($this->jsonContent, true);
            $decoded = $this->deepJsonDecode($content);
        if (!is_array($decoded)) {
            throw new ResponseParsingException(
                'Invalid JSON response',
                $this->jsonContent,
                'JSON'
            );
        }
        return $this->response = $decoded;
        } catch (ResponseParsingException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ResponseParsingException(
                "Error mapping Oracle response: {$e->getMessage()}",
                json_encode($this->response),
                'JSON'
            );
        }
    }

private function deepJsonDecode($value) {
    // If it's a string, try decoding
    if (is_string($value)) {
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // If the decoded value is still a string, try again (double-encoded case)
            if (is_string($decoded)) {
                $decoded2 = json_decode($decoded, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->deepJsonDecode($decoded2);
                }
            }
            return $this->deepJsonDecode($decoded);
        }
    }

    // If it's an array, recurse into each element
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            $value[$k] = $this->deepJsonDecode($v);
        }
    }

    return $value;
}



// $data = deepJsonDecode(json_decode($rawResponse, true));

    
}

