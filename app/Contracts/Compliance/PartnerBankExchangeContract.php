<?php

namespace App\Contracts\Compliance;

use App\Models\ComplianceCase;

interface PartnerBankExchangeContract
{
    /**
     * Transmit a compliance package to the partner bank for independent verification.
     *
     * @return array{
     *     status: string,
     *     bank_reference: string,
     *     transmitted_at: string,
     *     message: string
     * }
     */
    public function transmitComplianceDossier(ComplianceCase $case): array;

    /**
     * Query the partner bank's independent decision regarding an operation.
     *
     * @return array{
     *     bank_reference: string,
     *     bank_status: string,
     *     conditions: array<string>,
     *     allows_deposit: bool,
     *     allows_disbursement: bool,
     *     comments: ?string
     * }
     */
    public function fetchBankDecision(string $bankReference): array;
}
