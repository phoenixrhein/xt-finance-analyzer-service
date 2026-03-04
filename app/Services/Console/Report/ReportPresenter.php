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
    private int $categoryColWidth = 28;

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
     * UTF-8 aware string padding.
     * Pads string to the specified number of VISIBLE CHARACTERS (not bytes).
     */
    private function strPadUtf8(
        string $input,
        int $padLength,
        string $padString = ' ',
        int $padType = STR_PAD_RIGHT
    ): string {
        $inputLength = mb_strlen($input, 'UTF-8');
        if ($padLength <= $inputLength) {
            return $input;
        }

        $padStringLength = mb_strlen($padString, 'UTF-8');
        $padNeeded = $padLength - $inputLength;
        $fullPads = (int) ($padNeeded / $padStringLength);
        $remainder = $padNeeded % $padStringLength;
        $repeatString = str_repeat($padString, $fullPads) . mb_substr($padString, 0, $remainder, 'UTF-8');

        return match ($padType) {
            STR_PAD_LEFT => $repeatString . $input,
            STR_PAD_RIGHT => $input . $repeatString,
            STR_PAD_BOTH => $this->padBoth($input, $padLength, $padString, $padNeeded),
            default => $input,
        };
    }

    /**
     * Helper for STR_PAD_BOTH UTF-8 padding.
     */
    private function padBoth(string $input, int $padLength, string $padString, int $padNeeded): string
    {
        $padStringLength = mb_strlen($padString, 'UTF-8');
        $leftPad = (int) ($padNeeded / 2);
        $rightPad = $padNeeded - $leftPad;
        $leftFullPads = (int) ($leftPad / $padStringLength);
        $leftRemainder = $leftPad % $padStringLength;
        $rightFullPads = (int) ($rightPad / $padStringLength);
        $rightRemainder = $rightPad % $padStringLength;

        $leftString = str_repeat($padString, $leftFullPads) . mb_substr($padString, 0, $leftRemainder, 'UTF-8');
        $rightString = str_repeat($padString, $rightFullPads) . mb_substr($padString, 0, $rightRemainder, 'UTF-8');

        return $leftString . $input . $rightString;
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
        $hasSavingsData = $reportData->some(
            fn (PeriodReportData $p) => $p->toIgnoredIban !== 0.0 || $p->fromIgnoredIban !== 0.0
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
     * Dynamically calculates widths based on period headers, typical currency amounts, and category name lengths.
     */
    private function initializeColumnWidths(Collection $reportData): void
    {
        $this->columnWidths = [];

        // Use a fixed width of 16 characters for all amount columns
        // This ensures perfect alignment across header, data, and separator rows
        // 16 chars accommodates: "  -5.942,37 €" (12 chars) plus padding
        $columnWidth = 16;

        foreach ($reportData as $period) {
            $this->columnWidths[] = $columnWidth;
        }

        // Calculate maximum category column width by finding longest category name
        $maxCategoryLength = 9; // Minimum for "Kategorie" header
        foreach ($reportData as $period) {
            $maxCategoryLength = max(
                $maxCategoryLength,
                $this->findMaxCategoryLength($period->incomingCategories, '', 0)
            );
            $maxCategoryLength = max(
                $maxCategoryLength,
                $this->findMaxCategoryLength($period->outgoingCategories, '', 0)
            );
        }

        // Ensure minimum width of 35 to safely accommodate tree symbols and sub-item labels
        $this->categoryColWidth = max($maxCategoryLength, 35);

        // Build separator lines
        $this->buildSeparators();
    }

    /**
     * Find the maximum length of category names including prefixes.
     */
    private function findMaxCategoryLength(Collection $categories, string $prefix, int $depth): int
    {
        $maxLength = 0;

        foreach ($categories as $category) {
            // Category name line
            $nameLength = mb_strlen($prefix . $category->name, 'UTF-8');
            $maxLength = max($maxLength, $nameLength);

            // Only add sub-item lengths if category has children
            if ($category->children->isNotEmpty()) {
                $nextPrefix = $prefix . '  ';
                $maxLength = max($maxLength, mb_strlen($nextPrefix . '├─ zugeordnet', 'UTF-8'));
                $maxLength = max($maxLength, mb_strlen($nextPrefix . '├─ Summe Unterkategorien', 'UTF-8'));
                $maxLength = max($maxLength, mb_strlen($nextPrefix . '└─ Gesamt', 'UTF-8'));

                // Recursively check children
                if ($depth < 3) {
                    $childPrefix = $nextPrefix . '│  ├─ ';
                    $childMax = $this->findMaxCategoryLength($category->children, $childPrefix, $depth + 1);
                    $maxLength = max($maxLength, $childMax);
                }
            }
        }

        return $maxLength;
    }

    /**
     * Build table separator strings.
     */
    private function buildSeparators(): void
    {
        // For the category column: create the right number of dashes
        // We need categoryColWidth dashes to visually match the category column width
        $tableLine = str_repeat('─', $this->categoryColWidth);
        $headerLine = str_repeat('─', $this->categoryColWidth);

        foreach ($this->columnWidths as $width) {
            // Each data column is: '│' (1 char) + ' ' (1 char) + content ($width chars) =
            // (2 + $width) visible chars total
            // So we need the same visible character count: '┼' (1 char) + dashes (1 + $width chars)
            $tableLine .= '┼' . str_repeat('─', $width + 1);
            $headerLine .= '┼' . str_repeat('─', $width + 1);
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
     * Find a category node recursively in the tree.
     */
    private function findCategoryInTree(Collection $categories, int $categoryId): ?CategoryNode
    {
        foreach ($categories as $category) {
            if ($category->categoryId === $categoryId) {
                return $category;
            }

            // Search in children recursively
            if ($category->children->isNotEmpty()) {
                $found = $this->findCategoryInTree($category->children, $categoryId);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
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
            $found = $this->findCategoryInTree($categories, $category->categoryId);
            $nameRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '-';
        }
        $this->renderDataRow($nameRow);

        // Only show sub-rows (zugeordnet, Summe Unterkategorien, Gesamt) if category has children
        if ($category->children->isNotEmpty()) {
            $nextPrefix = $prefix . '  ';

            // Direct amount row
            $directRow = [$nextPrefix . '├─ zugeordnet'];
            foreach ($reportData as $period) {
                $categories = $getCategoriesCallback($period);
                $found = $this->findCategoryInTree($categories, $category->categoryId);
                $directRow[] = $found ? $this->formatAmount($found->directAmount) : '-';
            }
            $this->renderDataRow($directRow);

            // Children sum row
            $childrenRow = [$nextPrefix . '├─ Summe Unterkategorien'];
            foreach ($reportData as $period) {
                $categories = $getCategoriesCallback($period);
                $found = $this->findCategoryInTree($categories, $category->categoryId);
                $childrenRow[] = $found ? $this->formatAmount($found->childrenAmount) : '-';
            }
            $this->renderDataRow($childrenRow);

            // Render child categories (under "Summe Unterkategorien")
            $visibleChildren = $category->getVisibleChildren($this->showEmptyCategories, $this->maxCategoryDepth);

            // Determine tree symbols based on position
            foreach ($visibleChildren as $index => $child) {
                $isLast = $index === $visibleChildren->count() - 1;
                $childSymbol = $isLast ? '└─ ' : '├─ ';
                // Always use vertical line under children because "Gesamt" comes after
                $childrenWithVertical = $nextPrefix . '│  ';

                $this->renderCategoryRows($reportData, $child, $getCategoriesCallback, $childrenWithVertical . $childSymbol);
            }

            // Total row (after children)
            $totalRow = [$nextPrefix . '└─ Gesamt'];
            foreach ($reportData as $period) {
                $categories = $getCategoriesCallback($period);
                $found = $this->findCategoryInTree($categories, $category->categoryId);
                $totalRow[] = $found ? $this->formatAmount($found->getTotalAmount()) : '-';
            }
            $this->renderDataRow($totalRow);
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
        $padding = max(0, $this->categoryColWidth - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        $i = 0;
        foreach ($reportData as $period) {
            $header = $period->getPeriodHeader();
            $width = $this->columnWidths[$i] ?? 12;
            $line .= '│ ' . $this->strPadUtf8($header, $width, ' ', STR_PAD_BOTH);
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
        $padding = max(0, $this->categoryColWidth - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        foreach ($this->columnWidths as $i => $width) {
            $amount = $row[$i + 1] ?? '-';
            $line .= '│ ' . $this->strPadUtf8((string) $amount, $width, ' ', STR_PAD_LEFT);
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
        $padding = max(0, $this->categoryColWidth - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        foreach ($this->columnWidths as $i => $width) {
            $amount = $row[$i + 1] ?? '-';
            $line .= '│ ' . $this->strPadUtf8((string) $amount, $width, ' ', STR_PAD_LEFT);
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
