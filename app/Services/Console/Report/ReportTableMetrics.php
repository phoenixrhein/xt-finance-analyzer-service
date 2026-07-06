<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use Illuminate\Support\Collection;

/**
 * Calculates and manages table layout metrics for report rendering.
 * Computes column widths, separators, and category column sizing.
 */
class ReportTableMetrics
{
    /**
     * Column widths for amount columns.
     *
     * @var array
     */
    private array $columnWidths = [];

    /**
     * Width of the category name column.
     *
     * @var integer
     */
    private int $categoryColumnWidth = 18;

    /**
     * Table separator line.
     *
     * @var string
     */
    private string $tableSeparator = '';

    /**
     * Header separator line.
     *
     * @var string
     */
    private string $headerSeparator = '';

    /**
     * Maximum category depth to display.
     *
     * @var integer
     */
    private int $maxCategoryDepth;

    /**
     * Constructor.
     */
    public function __construct(int $maxCategoryDepth = 2)
    {
        $this->maxCategoryDepth = $maxCategoryDepth;
    }

    /**
     * Initialize column widths and separators from report data.
     */
    public function initialize(Collection $reportData): void
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
        // and by considering all table labels that may appear when no categories exist.
        $maxCategoryLength = mb_strlen(__('cli.report.presentation.label_category'), 'UTF-8');

        $additionalLabels = [
            __('cli.report.presentation.label_total') . ' ' . __('cli.report.presentation.title_income'),
            __('cli.report.presentation.label_total') . ' ' . __('cli.report.presentation.title_expenses'),
            __('cli.report.presentation.label_transfer_to_savings'),
            __('cli.report.presentation.label_transfer_from_savings'),
            __('cli.report.presentation.label_balance_change'),
            __('cli.report.presentation.label_income'),
            __('cli.report.presentation.label_expenses'),
            __('cli.report.presentation.label_balance'),
            __('cli.report.presentation.label_direct_amount'),
            __('cli.report.presentation.label_subcategories_sum'),
            __('cli.report.presentation.label_unassigned'),
        ];

        foreach ($additionalLabels as $label) {
            $maxCategoryLength = max($maxCategoryLength, mb_strlen($label, 'UTF-8'));
        }

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

        // Ensure minimum width of 15 to safely accommodate tree symbols and sub-item labels
        $this->categoryColumnWidth = max($maxCategoryLength, 15);

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
            $nameLength = mb_strlen($prefix . $category->name . ' ', 'UTF-8');
            $maxLength = max($maxLength, $nameLength);

            // Only add sub-item lengths if category has children
            if ($category->children->isNotEmpty()) {
                $nextPrefix = $prefix . '  ';
                // Use maximum expected lengths for direct amount and subcategories labels
                // '├─ zugeordnet' = 18 chars, '├─ Summe Unterkategorien ' = 27 chars
                $maxLength = max($maxLength, mb_strlen($nextPrefix . str_repeat('─', 18), 'UTF-8'));
                $maxLength = max($maxLength, mb_strlen($nextPrefix . str_repeat('─', 27), 'UTF-8'));

                // Recursively check children
                if ($depth < $this->maxCategoryDepth) {
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
        // We need categoryColumnWidth dashes to visually match the category column width
        $tableLine = str_repeat('─', $this->categoryColumnWidth);
        $headerLine = str_repeat('─', $this->categoryColumnWidth);

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
     * Get column widths array.
     */
    public function getColumnWidths(): array
    {
        return $this->columnWidths;
    }

    /**
     * Get category column width.
     */
    public function getCategoryColumnWidth(): int
    {
        return $this->categoryColumnWidth;
    }

    /**
     * Get table separator string.
     */
    public function getTableSeparator(): string
    {
        return $this->tableSeparator;
    }

    /**
     * Get header separator string.
     */
    public function getHeaderSeparator(): string
    {
        return $this->headerSeparator;
    }
}
