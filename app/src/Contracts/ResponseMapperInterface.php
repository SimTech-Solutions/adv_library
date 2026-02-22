<?php

declare(strict_types=1);

namespace AdvClientAPI\Contracts;

/**
 * Interface for response mapping
 */
interface ResponseMapperInterface
{
    /**
     * Map response to associative array
     *
     * @return array<string, mixed>
     */
    public function map(): array;
}
