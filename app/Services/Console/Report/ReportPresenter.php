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
    private array $columnWidths = [];
    private string $tableSeparator = '';
    private string $headerSeparator = '';
    private const CATEGORY_COL_WIDTH = 28;

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

        // Calculate column widths and separators once
        $this->initializeColumnWidths($reportData);

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
     * Initialize column widths and separator lines.
     * Dynamically calculates widths based on period headers and typical currency amounts.
     */
    private function initializeColumnWidths(Collection $reportData): void
    {
        $this->columnWidths = [];

        // Calculate width for each column based on header and typical currency format
        // Example format: "-1.234.567,89 €" (max ~15 chars with separators)
        foreach ($reportData as $period) {
            $headerLength = strlen($period->getPeriodHeader());
            // Minimum width should accommodate both header and formatted currency amounts
            // Using max of header + 2 or 14 (typical max currency width with € symbol)
            $this->columnWidths[] = max($headerLength + 2, 14);
        }

        // Build separator lines
        $this->buildSeparators();
    }

    /**
     * Build table separator strings.
     */
    private function buildSeparators(): void
    {
        $tableLine = str_repeat('─', self::CATEGORY_COL_WIDTH);
        $headerLine = str_repeat('─', self::CATEGORY_COL_WIDTH);

        foreach ($this->columnWidths as $width) {
            $tableLine .= '┼' . str_repeat('─', $width + 2);
            $headerLine .= '┼' . str_repeat('─', $width + 2);
        }

        $this->tableSeparator = $tableLine;
        $this->headerSeparator = $headerLine;
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
        $this->line($this->tableSeparator);

        // Render header
        $this->renderTableHeader($reportData);

        // Get the first period's categories as reference for hierarchy
        $firstPeriodCategories = $getCategoriesCallback($reportData->first());

        foreach ($firstPeriodCategories as $category) {
            $this->renderCategoryRows($reportData, $category, $getCategoriesCallback, '');
        }

        // Render total row
        $this->line($this->tableSeparator);
        $this->renderTotalRow($reportData, 'GESAMT ' . $title, $getTotalCallback);
    }

    /**
     * Render category and its detail rows recursively.
     */
    private function renderCategoryRows(
        Collection $reportData,
        CategoryNode $category,
        callable $getCategoriesCallback,
        string $prefix
    ): void {
        if (!$category->shouldDisplay($this->showEmptyCategories, $this->maxCategoryDepth)) {
            return;
        }

        // Handle "Unzugeordnet" (unassigned) categories - just show one line
        if ($category->categoryId === null) {
            $nameRow = ['(unzugeordnet)'];
            foreach ($reportData as $period) {
                $categories = $getCategoriesCallback($period);
                $found = $categories->firstWhere('categoryId', null);
                $nameRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '-';
            }
            $this->renderDataRow($nameRow);
            return;
        }

        // Show category name
        $nameRow = [$prefix . $category->name];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $nameRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '-';
        }
        $this->renderDataRow($nameRow);

        // Show sub-rows (zugeordnet, Summe Unterkategorien, Gesamt)
        $nextPrefix = $prefix . '  ';

        // Direct amount row
        $directRow = [$nextPrefix . '├─ zugeordnet'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $directRow[] = $found ? $this->formatAmount($found->directAmount) : '-';
        }
        $this->renderDataRow($directRow);

        // Children sum row
        $childrenRow = [$nextPrefix . '├─ Summe Unterkategorien'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $childrenRow[] = $found ? $this->formatAmount($found->childrenAmount) : '-';
        }
        $this->renderDataRow($childrenRow);

        // Total row
        $totalRow = [$nextPrefix . '└─ Gesamt'];
        foreach ($reportData as $period) {
            $categories = $getCategoriesCallback($period);
            $found = $categories->firstWhere('categoryId', $category->categoryId);
            $totalRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '-';
        }
        $this->renderDataRow($totalRow);

        // Render child categories
        $visibleChildren = $category->getVisibleChildren($this->showEmptyCategories, $this->maxCategoryDepth);
        $childrenWithVertical = $nextPrefix . '│';
        foreach ($visibleChildren as $child) {
            $this->renderCategoryRows($reportData, $child, $getCategoriesCallback, $childrenWithVertical . '  ├─ ');
        }
    }

    /**
     * Render the savings account table.
     */
    private function renderSavingsTable(Collection $reportData): void
    {
        $this->line('SPARBUCH (nicht in Einnahmen/Ausgaben enthalten)');
        $this->line($this->tableSeparator);

        // Render header
        $this->renderTableHeader($reportData);

        // Bankkonto → Sparbuch row
        $toRow = ['Bankkonto → Sparbuch'];
        foreach ($reportData as $period) {
            $toRow[] = $this->formatAmount($period->toIgnoredIban);
        }
        $this->renderDataRow($toRow);

        // Sparbuch → Bankkonto row
        $fromRow = ['Sparbuch → Bankkonto'];
        foreach ($reportData as $period) {
            $fromRow[] = $this->formatAmount($period->fromIgnoredIban);
        }
        $this->renderDataRow($fromRow);

        // Saldoveränderung row
        $this->line($this->tableSeparator);
        $balanceRow = ['Saldoveränderung'];
        foreach ($reportData as $period) {
            $balance = $period->toIgnoredIban + $period->fromIgnoredIban;
            $balanceRow[] = $this->formatAmount($balance);
        }
        $this->renderTotalRowData($balanceRow);
    }

    /**
     * Render summary table.
     */
    private function renderSummary(Collection $reportData): void
    {
        $this->line('ZUSAMMENFASSUNG');
        $this->line($this->tableSeparator);

        // Render header
        $this->renderTableHeader($reportData);

        // Income row
        $incomeRow = ['Einnahmen'];
        foreach ($reportData as $period) {
            $incomeRow[] = $this->formatAmount($period->totalIncome);
        }
        $this->renderDataRow($incomeRow);

        // Expenses row
        $expenseRow = ['Ausgaben'];
        foreach ($reportData as $period) {
            $expenseRow[] = $this->formatAmount(-$period->totalOutgoing);
        }
        $this->renderDataRow($expenseRow);

        // Balance row
        $this->line($this->tableSeparator);
        $balanceRow = ['Saldo'];
        foreach ($reportData as $period) {
            $balanceRow[] = $this->formatAmount($period->balance);
        }
        $this->renderTotalRowData($balanceRow);
    }

    /**
     * Render table header.
     */
    private function renderTableHeader(Collection $reportData): void
    {
        $categoryText = 'Kategorie';
        $categoryLength = mb_strlen($categoryText, 'UTF-8');
        $padding = max(0, self::CATEGORY_COL_WIDTH - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        $i = 0;
        foreach ($reportData as $period) {
            $header = $period->getPeriodHeader();
            $width = $this->columnWidths[$i] ?? 12;
            $line .= '│ ' . str_pad($header, $width, ' ', STR_PAD_BOTH);
            $i++;
        }
        $this->line($line);
        $this->line($this->headerSeparator);
    }

    /**
     * Render data row with right-aligned amounts.
     */
    private function renderDataRow(array $row): void
    {
        $categoryText = $row[0];
        $categoryLength = mb_strlen($categoryText, 'UTF-8');
        $padding = max(0, self::CATEGORY_COL_WIDTH - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        foreach ($this->columnWidths as $i => $width) {
            $amount = $row[$i + 1] ?? '-';
            $line .= '│ ' . str_pad((string) $amount, $width, ' ', STR_PAD_LEFT);
        }
        $this->line($line);
    }

    /**
     * Render total row (bold).
     */
    private function renderTotalRow(Collection $reportData, string $label, callable $getTotalCallback): void
    {
        $row = [$label];
        foreach ($reportData as $period) {
            $row[] = $this->formatAmount($getTotalCallback($period));
        }
        $this->renderTotalRowData($row);
    }

    /**
     * Render total row data (bold).
     */
    private function renderTotalRowData(array $row): void
    {
        $categoryText = $row[0];
        $categoryLength = mb_strlen($categoryText, 'UTF-8');
        $padding = max(0, self::CATEGORY_COL_WIDTH - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        foreach ($this->columnWidths as $i => $width) {
            $amount = $row[$i + 1] ?? '-';
            $line .= '│ ' . str_pad((string) $amount, $width, ' ', STR_PAD_LEFT);
        }
        $this->line('<info>' . $line . '</info>');
    }

    /**
     * Format amount according to configuration.
     */
    private function formatAmount(float $amount): string
    {
        if ($amount === 0.0) {
            return '0 €';
        }

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
