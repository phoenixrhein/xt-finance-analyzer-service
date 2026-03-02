<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use de\xovatec\financeAnalyzer\Enums\TimespanType;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Console\Report\DTO\CategoryNode;
use de\xovatec\financeAnalyzer\Services\Console\Report\DTO\PeriodReportData;
use Illuminate\Support\Collection;

/**
 * Presents report data in formatted console tables.
 * Renders income, expenses, and savings account transfers across multiple periods.
 */
class ReportPresenter extends AbstractIOService
{
    private string $thousandSeparator;
    private string $decimalSeparator;
    private int $decimals;
    private bool $showEmptyCategories;
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

        // Check if savings account data exists
        $hasSavingsData = $reportData->some(fn (PeriodReportData $p) =>
            $p->toIgnoredIban !== 0.0 || $p->fromIgnoredIban !== 0.0
        );

        // Render income table
        $this->renderCategoryTable(
            $reportData,
            'EINNAHMEN',
            fn (PeriodReportData $p) => $p->incomingCategories,
            fn (PeriodReportData $p) => $p->totalIncome
        );
        $this->newLine();

        // Render expenses table
        $this->renderCategoryTable(
            $reportData,
            'AUSGABEN',
            fn (PeriodReportData $p) => $p->outgoingCategories,
            fn (PeriodReportData $p) => -$p->totalOutgoing
        );
        $this->newLine();

        // Render savings account table if applicable
        if ($hasSavingsData) {
            $this->renderSavingsTable($reportData);
            $this->newLine();
        }

        // Render summary table
        $this->renderSummary($reportData);
    }

    /**
     * Render a category table with hierarchical structure across multiple periods.
     */
    private function renderCategoryTable(
        Collection $reportData,
        string $title,
        callable $getCategoriesCallback,
        callable $getTotalCallback
    ): void {
        $this->line($title);
        $this->line(str_repeat('─', 80));

        // Build header row with period names
        $headers = ['Kategorie'];
        foreach ($reportData as $period) {
            $headers[] = $period->getPeriodHeader();
        }
        $this->renderTableHeader($headers);

        // Get the first period's categories as reference for hierarchy
        $firstPeriodCategories = $getCategoriesCallback($reportData->first());

        foreach ($firstPeriodCategories as $category) {
            $this->renderCategoryWithData($reportData, $category, $getCategoriesCallback, 0);
        }

        // Render total row
        $this->line(str_repeat('─', 28) . '┼' . str_repeat('─', 52));
        $totalRow = ['GESAMT ' . $title];
        foreach ($reportData as $period) {
            $totalRow[] = $this->formatAmount($getTotalCallback($period));
        }
        $this->renderTableRow($totalRow, true);
    }

    /**
     * Render a category with its data from all periods.
     */
    private function renderCategoryWithData(
        Collection $reportData,
        CategoryNode $category,
        callable $getCategoriesCallback,
        int $depth
    ): void {
        if (!$category->shouldDisplay($this->showEmptyCategories, $this->maxCategoryDepth)) {
            return;
        }

        $indent = $this->getIndent($depth);

        // Show parent category name
        $nameRow = [$indent . $category->name];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $nameRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '0 €';
        }
        $this->renderTableRow($nameRow, false);

        // Show subcategories
        $visibleChildren = $category->getVisibleChildren($this->showEmptyCategories, $this->maxCategoryDepth);
        foreach ($visibleChildren as $child) {
            $this->renderSubcategoryWithDetails($reportData, $child, $getCategoriesCallback, $depth + 1);
        }
    }

    /**
     * Render subcategory with direct/subtotal breakdown.
     */
    private function renderSubcategoryWithDetails(
        Collection $reportData,
        CategoryNode $category,
        callable $getCategoriesCallback,
        int $depth
    ): void {
        if (!$category->shouldDisplay($this->showEmptyCategories, $this->maxCategoryDepth)) {
            return;
        }

        $indent = $this->getIndent($depth);

        // Direct amount row
        $directRow = [$indent . '  ├─ zugeordnet'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $directRow[] = $found ? $this->formatAmount($found->directAmount) : '0 €';
        }
        $this->renderTableRow($directRow, false);

        // Children sum row
        $childrenRow = [$indent . '  ├─ Summe Unterkategorien'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $childrenRow[] = $found ? $this->formatAmount($found->childrenAmount) : '0 €';
        }
        $this->renderTableRow($childrenRow, false);

        // Total row
        $totalRow = [$indent . '  └─ Gesamt'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $totalRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '0 €';
        }
        $this->renderTableRow($totalRow, false);

        // Show nested children (3rd level)
        $visibleChildren = $category->getVisibleChildren($this->showEmptyCategories, $this->maxCategoryDepth);
        foreach ($visibleChildren as $child) {
            $this->renderNestedChild($reportData, $child, $getCategoriesCallback, $depth + 1);
        }
    }

    /**
     * Render nested child category (3rd+ level).
     */
    private function renderNestedChild(
        Collection $reportData,
        CategoryNode $category,
        callable $getCategoriesCallback,
        int $depth
    ): void {
        if (!$category->shouldDisplay($this->showEmptyCategories, $this->maxCategoryDepth)) {
            return;
        }

        $indent = $this->getIndent($depth);

        // Direct amount row
        $directRow = [$indent . '  ├─ zugeordnet'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $directRow[] = $found ? $this->formatAmount($found->directAmount) : '0 €';
        }
        $this->renderTableRow($directRow, false);

        // Children sum row
        $childrenRow = [$indent . '  ├─ Summe Unterkategorien'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $childrenRow[] = $found ? $this->formatAmount($found->childrenAmount) : '0 €';
        }
        $this->renderTableRow($childrenRow, false);

        // Total row
        $totalRow = [$indent . '  └─ Gesamt'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $totalRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '0 €';
        }
        $this->renderTableRow($totalRow, false);
    }

    /**
     * Render the savings account table.
     */
    private function renderSavingsTable(Collection $reportData): void
    {
        $this->line('SPARBUCH (nicht in Einnahmen/Ausgaben enthalten)');
        $this->line(str_repeat('─', 71));

        // Build header row
        $headers = ['Richtung'];
        foreach ($reportData as $period) {
            $headers[] = $period->getPeriodHeader();
        }
        $this->renderTableHeader($headers);

        // Bankkonto → Sparbuch row
        $toRow = ['Bankkonto → Sparbuch'];
        foreach ($reportData as $period) {
            $toRow[] = $this->formatAmount($period->toIgnoredIban);
        }
        $this->renderTableRow($toRow, false);

        // Sparbuch → Bankkonto row
        $fromRow = ['Sparbuch → Bankkonto'];
        foreach ($reportData as $period) {
            $fromRow[] = $this->formatAmount($period->fromIgnoredIban);
        }
        $this->renderTableRow($fromRow, false);

        // Saldoveränderung row
        $this->line(str_repeat('─', 28) . '┼' . str_repeat('─', 43));
        $balanceRow = ['Saldoveränderung'];
        foreach ($reportData as $period) {
            $balance = $period->toIgnoredIban + $period->fromIgnoredIban;
            $balanceRow[] = $this->formatAmount($balance);
        }
        $this->renderTableRow($balanceRow, true);
    }

    /**
     * Render summary table.
     */
    private function renderSummary(Collection $reportData): void
    {
        $this->line('ZUSAMMENFASSUNG');
        $this->line(str_repeat('─', 71));

        // Build header row
        $headers = ['Kategorie'];
        foreach ($reportData as $period) {
            $headers[] = $period->getPeriodHeader();
        }
        $this->renderTableHeader($headers);

        // Income row
        $incomeRow = ['Einnahmen'];
        foreach ($reportData as $period) {
            $incomeRow[] = $this->formatAmount($period->totalIncome);
        }
        $this->renderTableRow($incomeRow, false);

        // Expenses row
        $expenseRow = ['Ausgaben'];
        foreach ($reportData as $period) {
            $expenseRow[] = $this->formatAmount(-$period->totalOutgoing);
        }
        $this->renderTableRow($expenseRow, false);

        // Balance row
        $this->line(str_repeat('─', 28) . '┼' . str_repeat('─', 43));
        $balanceRow = ['Saldo'];
        foreach ($reportData as $period) {
            $balanceRow[] = $this->formatAmount($period->balance);
        }
        $this->renderTableRow($balanceRow, true);
    }

    /**
     * Render table header.
     */
    private function renderTableHeader(array $headers): void
    {
        $line = '';
        foreach ($headers as $i => $header) {
            if ($i === 0) {
                $line .= str_pad($header, 28);
            } else {
                $line .= '│ ' . str_pad($header, 11);
            }
        }
        $this->line($line);
        $this->line(str_repeat('─', 28) . '┼' . str_repeat('─', 52));
    }

    /**
     * Render table row.
     */
    private function renderTableRow(array $row, bool $isBold = false): void
    {
        $line = '';
        foreach ($row as $i => $cell) {
            $cell = (string) $cell;
            if ($i === 0) {
                $line .= str_pad($cell, 28);
            } else {
                $line .= '│ ' . str_pad($cell, 11);
            }
        }
        if ($isBold) {
            $this->line('<info>' . $line . '</info>');
        } else {
            $this->line($line);
        }
    }

    /**
     * Get indentation string for category depth.
     */
    private function getIndent(int $depth): string
    {
        if ($depth === 0) {
            return '';
        }
        return str_repeat('  ', $depth - 1) . '  ';
    }

    /**
     * Format amount according to configuration.
     */
    private function formatAmount(float $amount): string
    {
        $isNegative = $amount < 0;
        $absolute = abs($amount);

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
