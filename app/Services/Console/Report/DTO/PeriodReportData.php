<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report\DTO;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Data Transfer Object for report data of a single period.
 * Contains categorized transactions for income, expenses and totals.
 */
class PeriodReportData
{
    /**
     * @param Carbon $periodStart Start date of the period
     * @param Carbon $periodEnd End date of the period
     * @param Collection<CategoryNode> $incomingCategories Income categories with transactions
     * @param Collection<CategoryNode> $outgoingCategories Expense categories with transactions
     * @param float $totalIncome Sum of all income transactions
     * @param float $totalOutgoing Sum of all expense transactions
     * @param float $balance Difference between income and expenses
     * @param float $toIgnoredIban Sum of transactions TO ignored IBANs (negative values, shown as outgoing)
     * @param float $fromIgnoredIban Sum of transactions FROM ignored IBANs (positive values, shown as incoming)
     */
    public function __construct(
        public readonly Carbon $periodStart,
        public readonly Carbon $periodEnd,
        public readonly Collection $incomingCategories,
        public readonly Collection $outgoingCategories,
        public readonly float $totalIncome,
        public readonly float $totalOutgoing,
        public readonly float $balance,
        public readonly float $toIgnoredIban = 0.0,
        public readonly float $fromIgnoredIban = 0.0,
    ) {
    }

    /**
     * Get a human-readable period label.
     * Examples: "Mai 2025", "2025"
     */
    public function getPeriodLabel(): string
    {
        if ($this->periodStart->month === $this->periodEnd->month &&
            $this->periodStart->year === $this->periodEnd->year) {
            return $this->periodStart->translatedFormat('F Y');
        }

        return sprintf(
            '%s - %s',
            $this->periodStart->translatedFormat('F Y'),
            $this->periodEnd->translatedFormat('F Y')
        );
    }

    /**
     * Get period range for table output (e.g., "Mai", "Juni", "Juli").
     */
    public function getPeriodHeader(): string
    {
        return $this->periodStart->translatedFormat('F');
    }
}
