<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use de\xovatec\financeAnalyzer\Helpers\StringMbUtils;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Traits\Console\CurrencyFormatterTrait;
use Illuminate\Support\Collection;

/**
 * Renders individual table rows and headers with proper formatting and alignment.
 * Handles data rows, header rows, and total rows with correct column width calculation.
 */
class ReportTableRenderer extends AbstractIOService
{
    use CurrencyFormatterTrait;

    /**
     * Constructor.
     *
     * @
     */
    public function __construct(private ReportTableMetrics $metrics)
    {
        parent::__construct();
        $this->metrics = $metrics;
        $this->initializeCurrencyFormatter();
    }

    /**
     * Render table header with period labels.
     */
    public function renderTableHeader(Collection $reportData): void
    {
        $categoryText = __('cli.report.presentation.label_category');
        $categoryLength = mb_strlen($categoryText, 'UTF-8');
        $padding = max(0, $this->metrics->getCategoryColumnWidth() - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        $i = 0;
        foreach ($reportData as $period) {
            $header = $period->getPeriodHeader();
            $width = $this->metrics->getColumnWidths()[$i] ?? 12;
            $line .= '│ ' . StringMbUtils::strPadUtf8($header, $width, ' ', STR_PAD_BOTH);
            $i++;
        }
        $this->line($line);
        $this->line($this->metrics->getHeaderSeparator());
    }

    /**
     * Render a header for a table with one amount column.
     */
    public function renderSingleColumnTableHeader(string $header): void
    {
        $categoryText = __('cli.report.presentation.label_category');
        $categoryLength = mb_strlen($categoryText, 'UTF-8');
        $padding = max(0, $this->metrics->getCategoryColumnWidth() - $categoryLength);
        $width = $this->metrics->getColumnWidths()[0] ?? 12;

        $line = $categoryText . str_repeat(' ', $padding);
        $line .= '│ ' . StringMbUtils::strPadUtf8($header, $width, ' ', STR_PAD_BOTH);

        $this->line($line);
        $this->renderSingleColumnTableSeparator();
    }

    /**
     * Render a data row with right-aligned amounts.
     */
    public function renderDataRow(array $row): void
    {
        $categoryText = $row[0];
        $categoryLength = mb_strlen($categoryText, 'UTF-8');
        $padding = max(0, $this->metrics->getCategoryColumnWidth() - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        foreach ($this->metrics->getColumnWidths() as $i => $width) {
            $amount = $row[$i + 1] ?? '-';
            $line .= '│ ' . StringMbUtils::strPadUtf8((string) $amount, $width, ' ', STR_PAD_LEFT);
        }
        $this->line($line);
    }

    /**
     * Render a data row for a table with one amount column.
     */
    public function renderSingleColumnDataRow(array $row): void
    {
        $this->renderSingleColumnRow($row, false);
    }

    /**
     * Render a total row (bold).
     */
    public function renderTotalRow(Collection $reportData, string $label, callable $getTotalCallback): void
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
    public function renderTotalRowData(array $row): void
    {
        $categoryText = $row[0];
        $categoryLength = mb_strlen($categoryText, 'UTF-8');
        $padding = max(0, $this->metrics->getCategoryColumnWidth() - $categoryLength);
        $line = $categoryText . str_repeat(' ', $padding);

        foreach ($this->metrics->getColumnWidths() as $i => $width) {
            $amount = $row[$i + 1] ?? '-';
            $line .= '│ ' . StringMbUtils::strPadUtf8((string) $amount, $width, ' ', STR_PAD_LEFT);
        }
        $this->line('<info>' . $line . '</info>');
    }

    /**
     * Render total row data for a table with one amount column.
     */
    public function renderSingleColumnTotalRowData(array $row): void
    {
        $this->renderSingleColumnRow($row, true);
    }

    /**
     * Render separator line.
     */
    public function renderTableSeparator(): void
    {
        $this->line($this->metrics->getTableSeparator());
    }

    /**
     * Render separator line for a table with one amount column.
     */
    public function renderSingleColumnTableSeparator(): void
    {
        $width = $this->metrics->getColumnWidths()[0] ?? 12;
        $separator = str_repeat('─', $this->metrics->getCategoryColumnWidth());
        $separator .= '┼' . str_repeat('─', $width + 1);

        $this->line($separator);
    }

    private function renderSingleColumnRow(array $row, bool $isTotal): void
    {
        $categoryText = $row[0];
        $categoryLength = mb_strlen($categoryText, 'UTF-8');
        $padding = max(0, $this->metrics->getCategoryColumnWidth() - $categoryLength);
        $width = $this->metrics->getColumnWidths()[0] ?? 12;

        $line = $categoryText . str_repeat(' ', $padding);
        $line .= '│ ' . StringMbUtils::strPadUtf8((string) ($row[1] ?? '-'), $width, ' ', STR_PAD_LEFT);

        $this->line($isTotal ? '<info>' . $line . '</info>' : $line);
    }
}
