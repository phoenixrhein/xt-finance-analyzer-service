<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use de\xovatec\financeAnalyzer\Enums\TimespanType;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Console\Report\DTO\CategoryNode;
use de\xovatec\financeAnalyzer\Services\Console\Report\DTO\PeriodReportData;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;

/**
 * Presents report data in formatted console tables.
 */
class ReportPresenter extends AbstractIOService
{
    /**
     *
     * @var string
     */
    private string $thousandSeparator;

    /**
     *
     * @var string
     */
    private string $decimalSeparator;

    /**
     *
     * @var integer
     */
    private int $decimals;

    /**
     *
     * @var boolean
     */
    private bool $showEmptyCategories;

    /**
     *
     * @var integer
     */
    private int $maxCategoryDepth;

    public function __construct()
    {
        parent::__construct();
        $this->thousandSeparator = config('report.display.currency_thousand_separator', '.');
        $this->decimalSeparator = config('report.display.currency_decimal_separator', ',');
        $this->decimals = config('report.display.currency_decimals', 2);
        $this->showEmptyCategories = config('report.display.show_empty_categories', false);
        $this->maxCategoryDepth = config('report.display.max_category_depth', 2);
    }

    /**
     * Render all report periods to console.
     */
    public function render(Collection $reportData, ?TimespanType $timespanType = null): void
    {
        if ($reportData->isEmpty()) {
            $this->info('Keine Daten für den gewählten Zeitraum vorhanden.');
            return;
        }

        foreach ($reportData as $periodData) {
            $this->renderPeriod($periodData);
            $this->newLine();
        }

        $this->renderSummary($reportData);
    }

    /**
     * Render a single period with income, expense and summary tables.
     */
    private function renderPeriod(PeriodReportData $periodData): void
    {
        $this->info("Zeitraum: {$periodData->getPeriodLabel()}");
        $this->newLine();

        if ($periodData->incomingCategories->isNotEmpty()) {
            $this->line('Einnahmen:');
            $this->renderCategoryTable($periodData->incomingCategories, $periodData->getPeriodHeader());
            $this->newLine();
        }

        if ($periodData->outgoingCategories->isNotEmpty()) {
            $this->line('Ausgaben:');
            $this->renderCategoryTable($periodData->outgoingCategories, $periodData->getPeriodHeader());
            $this->newLine();
        }
    }

    /**
     * Render a category hierarchy table.
     */
    private function renderCategoryTable(Collection $categories, string $periodHeader): void
    {
        $rows = [];

        foreach ($categories as $category) {
            $this->addCategoryRows($rows, $category, 0);
        }

        if (empty($rows)) {
            $this->line('  (Keine Daten)');
            return;
        }

        $this->table(
            ['Kategorie', $periodHeader],
            $rows
        );
    }

    /**
     * Recursively add category rows for hierarchical display.
     */
    private function addCategoryRows(array &$rows, CategoryNode $category, int $currentDepth): void
    {
        if (!$category->shouldDisplay($this->showEmptyCategories, $this->maxCategoryDepth)) {
            return;
        }

        $indent = str_repeat('  ', $currentDepth);
        $prefix = $currentDepth > 0 ? '└ ' : '';
        $categoryName = $prefix . $category->name;

        $rows[] = [
            $indent . $categoryName,
            $this->formatAmount($category->getTotalAmount()),
        ];

        foreach ($category->getVisibleChildren($this->showEmptyCategories, $this->maxCategoryDepth) as $child) {
            $this->addCategoryRows($rows, $child, $currentDepth + 1);
        }
    }

    /**
     * Render summary table across all periods.
     */
    private function renderSummary(Collection $reportData): void
    {
        $this->line('Zusammenfassung:');

        $headerRow = ['Kategorie'];
        $summaryRows = [
            ['Einnahmen'],
            ['Ausgaben'],
            ['Saldo'],
        ];

        foreach ($reportData as $periodData) {
            $headerRow[] = $periodData->getPeriodHeader();

            $summaryRows[0][] = $this->formatAmount($periodData->totalIncome);
            $summaryRows[1][] = $this->formatAmount(-$periodData->totalOutgoing);
            $summaryRows[2][] = $this->formatAmount($periodData->balance);
        }

        $this->table($headerRow, $summaryRows);
    }

    /**
     * Format amount according to configuration.
     *
     * @param float $amount The amount to format (can be negative)
     * @return string Formatted currency string
     */
    private function formatAmount(float $amount): string
    {
        $isNegative = $amount < 0;
        $absolute = abs($amount);

        // Format with decimals
        $formatted = number_format(
            $absolute,
            $this->decimals,
            $this->decimalSeparator,
            $this->thousandSeparator
        );

        $sign = $isNegative ? '-' : '';

        return "{$sign}{$formatted} €";
    }
}
