<?php

namespace de\xovatec\financeAnalyzer\Dto\Report;

use Carbon\Carbon;

/**
 * Data Transfer Object for report data of a single savings account.
 */
class SavingsAccountReportData
{
    public function __construct(
        public readonly int $exclusionListId,
        public readonly string $comment,
        public readonly string $ibanLastThree,
        public readonly Carbon $periodStart,
        public readonly Carbon $periodEnd,
        public readonly float $toExcludedIban = 0.0,
        public readonly float $fromExcludedIban = 0.0,
    ) {
    }
}
