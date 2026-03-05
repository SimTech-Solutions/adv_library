<?php

declare(strict_types=1);

namespace AdvClientAPI\Contracts;

/**
 * Interface for insurance services
 */
interface InsuranceServiceInterface
{
    /**
     * Perform pharma act operation
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function performPharmaAct(array $data): array;

    /**
     * Add invoice operation
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function addInvoice(array $data): array;

    /**
     * Create eligibility operation
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createEligibility(array $data): array;

    /**
     * Cancel eligibility operation
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function cancelEligibility(array $data): array;
}
