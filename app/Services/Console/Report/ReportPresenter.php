<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use de\xovatec\financeAnalyzer\Enums\TimespanType;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Console\Report\CategoryTreeRenderer;
use de\xovatec\financeAnalyzer\Services\Console\Report\DTO\PeriodReportData;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportTableMetrics;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportTableRenderer;
use de\xovatec\financeAnalyzer\Traits\Console\CurrencyFormatterTrait;
use Illuminate\Support\Collection;

class ReportPresenter extends AbstractIOService
{
    use CurrencyFormatterTrait;

    /**
     * Table metrics for sizing and layout.
     *
     * @var ReportTableMetrics
     */
    private ReportTableMetrics $tableMetrics;

    /**
     * Table renderer for rendering rows.
     *
     * @var ReportTableRenderer
     */
    private ReportTableRenderer $tableRenderer;

    /**
     * Category tree renderer for hierarchical category display.
     *
     * @var CategoryTreeRenderer
     */
    private CategoryTreeRenderer $categoryTreeRenderer;

    /**
     * Configuration: show empty categories.
     *
     * @var boolean
     */
    private bool $showEmptyCategories;

    /**
     * Configuration: maximum category depth to display.
     *
     * @var integer
     */
    private int $maxCategoryDepth;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->initializeCurrencyFormatter();

        // Load configuration
        $this->showEmptyCategories = config('report.display.show_empty_categories', false);
        $this->maxCategoryDepth = config('report.display.max_category_depth', 2);

        // Initialize specialized renderers
        $this->tableMetrics = new ReportTableMetrics($this->maxCategoryDepth);
        $this->tableRenderer = new ReportTableRenderer($this->tableMetrics);
        $this->categoryTreeRenderer = new CategoryTreeRenderer(
            $this->tableRenderer,
            $this->maxCategoryDepth,
            $this->showEmptyCategories
        );
    }

    /**
     * Main method to render the report. Handles all sections and tables.
     *
     * @param Collection $reportData
     * @param TimespanType|null $timespanType
     * @return void
     */
    public function render(Collection $reportData, ?TimespanType $timespanType = null): void
    {
        if ($reportData->isEmpty()) {
            $this->info(__('cli.report.presentation.no_data'));
            return;
        }

        // Check if savings account data exists
        $hasSavingsData = $reportData->some(
            fn (PeriodReportData $p) => $p->toIgnoredIban !== 0.0 || $p->fromIgnoredIban !== 0.0
        );

        // Calculate column widths and separators once
        $this->tableMetrics->initialize($reportData);

        // Render income table
        $this->renderCategoryTable(
            $reportData,
            __('cli.report.presentation.title_income'),
            fn (PeriodReportData $p) => $p->incomingCategories,
            fn (PeriodReportData $p) => $p->totalIncome
        );
        $this->newLine();

        // Render expenses table
        $this->renderCategoryTable(
            $reportData,
            __('cli.report.presentation.title_expenses'),
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
     *
     * @param Collection $reportData
     * @param string $title
     * @param callable $getCategoriesCallback
     * @param callable $getTotalCallback
     * @return void
     */
    private function renderCategoryTable(
        Collection $reportData,
        string $title,
        callable $getCategoriesCallback,
        callable $getTotalCallback
    ): void {
        $this->line($title);
        $this->tableRenderer->renderTableSeparator();

        // Render header
        $this->tableRenderer->renderTableHeader($reportData);

        // Get the first period's categories as reference for hierarchy
        $firstPeriodCategories = $getCategoriesCallback($reportData->first());

        foreach ($firstPeriodCategories as $category) {
            $this->categoryTreeRenderer->renderCategoryRows($reportData, $category, $getCategoriesCallback, '');
        }

        // Render total row
        $this->tableRenderer->renderTableSeparator();
        $this->tableRenderer->renderTotalRow($reportData, __('cli.report.presentation.label_total') . ' ' . $title, $getTotalCallback);
    }

    /**
     * Render the savings account table.
     *
     * @param Collection $reportData
     * @return void
     */
    private function renderSavingsTable(Collection $reportData): void
    {
        $this->line(__('cli.report.presentation.title_savings_account'));
        $this->tableRenderer->renderTableSeparator();

        // Render header
        $this->tableRenderer->renderTableHeader($reportData);

        // Bankkonto → Sparbuch row (invert sign: money coming in is positive)
        $toRow = [__('cli.report.presentation.label_transfer_to_savings')];
        foreach ($reportData as $period) {
            $toRow[] = $this->formatAmount(-$period->toIgnoredIban);
        }
        $this->tableRenderer->renderDataRow($toRow);

        // Sparbuch → Bankkonto row (invert sign: money going out is negative)
        $fromRow = [__('cli.report.presentation.label_transfer_from_savings')];
        foreach ($reportData as $period) {
            $fromRow[] = $this->formatAmount(-$period->fromIgnoredIban);
        }
        $this->tableRenderer->renderDataRow($fromRow);

        // Saldoveränderung row (total change in savings account)
        $this->tableRenderer->renderTableSeparator();
        $balanceRow = [__('cli.report.presentation.label_balance_change')];
        foreach ($reportData as $period) {
            // Balance change = money in + money out (with inverted signs)
            $balance = -$period->toIgnoredIban + (-$period->fromIgnoredIban);
            $balanceRow[] = $this->formatAmount($balance);
        }
        $this->tableRenderer->renderTotalRowData($balanceRow);
    }

    /**
     * Render summary table.
     *
     * @param Collection $reportData
     * @return void
     */
    private function renderSummary(Collection $reportData): void
    {
        $this->line(__('cli.report.presentation.title_summary'));
        $this->tableRenderer->renderTableSeparator();

        // Render header
        $this->tableRenderer->renderTableHeader($reportData);

        // Income row
        $incomeRow = [__('cli.report.presentation.label_income')];
        foreach ($reportData as $period) {
            $incomeRow[] = $this->formatAmount($period->totalIncome);
        }
        $this->tableRenderer->renderDataRow($incomeRow);

        // Expenses row
        $expenseRow = [__('cli.report.presentation.label_expenses')];
        foreach ($reportData as $period) {
            $expenseRow[] = $this->formatAmount(-$period->totalOutgoing);
        }
        $this->tableRenderer->renderDataRow($expenseRow);

        // Balance row
        $this->tableRenderer->renderTableSeparator();
        $balanceRow = [__('cli.report.presentation.label_balance')];
        foreach ($reportData as $period) {
            $balanceRow[] = $this->formatAmount($period->balance);
        }
        $this->tableRenderer->renderTotalRowData($balanceRow);
    }
}
