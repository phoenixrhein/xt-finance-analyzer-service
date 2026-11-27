<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use de\xovatec\financeAnalyzer\Enums\TimespanType;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Console\Report\CategoryTreeRenderer;
use de\xovatec\financeAnalyzer\Dto\Report\PeriodReportData;
use de\xovatec\financeAnalyzer\Dto\Report\SavingsAccountReportData;
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

        $savingsAccounts = $this->getSavingsAccounts($reportData);

        // Calculate column widths and separators once
        $this->tableMetrics->initialize(
            $reportData,
            $savingsAccounts
                ->map(fn (SavingsAccountReportData $account) => $this->getSavingsAccountTitle($account))
                ->all()
        );

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
            fn (PeriodReportData $p) => $p->totalOutgoing
        );
        $this->newLine();

        // Render savings account table if applicable
        if ($savingsAccounts->isNotEmpty()) {
            $this->renderSavingsTables($reportData, $savingsAccounts);
            $this->newLine();
        }

        // Render summary table
        $this->renderSummary($reportData);

        $this->newLine();

        // Render total overview across all periods
        $this->renderTotalOverview($reportData);
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
        $this->tableRenderer->renderTotalRow(
            $reportData,
            __('cli.report.presentation.label_total') . ' ' . $title,
            $getTotalCallback
        );
    }

    /**
     * Collects savings accounts that have transactions in at least one period.
     *
     * @return Collection<SavingsAccountReportData>
     */
    private function getSavingsAccounts(Collection $reportData): Collection
    {
        return $reportData
            ->flatMap(fn (PeriodReportData $period) => $period->savingsAccounts)
            ->unique('exclusionListId')
            ->filter(function (SavingsAccountReportData $account) use ($reportData): bool {
                return $reportData->contains(
                    fn (PeriodReportData $period) => $period->savingsAccounts->contains(
                        fn (SavingsAccountReportData $periodAccount) =>
                            $periodAccount->exclusionListId === $account->exclusionListId
                            && (
                                $periodAccount->toExcludedIban !== 0.0
                                || $periodAccount->fromExcludedIban !== 0.0
                            )
                    )
                );
            })
            ->values();
    }

    /**
     * Render one savings table per excluded IBAN.
     *
     * @param  Collection<PeriodReportData>  $reportData
     * @param  Collection<SavingsAccountReportData>  $savingsAccounts
     */
    private function renderSavingsTables(Collection $reportData, Collection $savingsAccounts): void
    {
        foreach ($savingsAccounts->values() as $index => $account) {
            $this->line($this->getSavingsAccountTitle($account));
            $this->tableRenderer->renderTableSeparator(true);
            $this->tableRenderer->renderTableHeader($reportData, true);

            $toAmounts = [];
            $fromAmounts = [];

            foreach ($reportData as $period) {
                $periodAccount = $period->savingsAccounts->firstWhere('exclusionListId', $account->exclusionListId);
                $toAmounts[] = -($periodAccount?->toExcludedIban ?? 0.0);
                $fromAmounts[] = -($periodAccount?->fromExcludedIban ?? 0.0);
            }

            $toRow = [__('cli.report.presentation.label_transfer_to_savings')];
            foreach ($toAmounts as $amount) {
                $toRow[] = $this->formatSavingsAmount($amount);
            }
            $toRow[] = $this->formatSavingsAmount(array_sum($toAmounts));
            $this->tableRenderer->renderDataRow($toRow, true);

            $fromRow = [__('cli.report.presentation.label_transfer_from_savings')];
            foreach ($fromAmounts as $amount) {
                $fromRow[] = $this->formatSavingsAmount($amount);
            }
            $fromRow[] = $this->formatSavingsAmount(array_sum($fromAmounts));
            $this->tableRenderer->renderDataRow($fromRow, true);

            $this->tableRenderer->renderTableSeparator(true);
            $balanceAmounts = array_map(
                fn (float $toAmount, float $fromAmount) => $toAmount + $fromAmount,
                $toAmounts,
                $fromAmounts
            );
            $balanceRow = [__('cli.report.presentation.label_balance_change')];
            foreach ($balanceAmounts as $amount) {
                $balanceRow[] = $this->formatSavingsAmount($amount);
            }
            $balanceRow[] = $this->formatSavingsAmount(array_sum($balanceAmounts));
            $this->tableRenderer->renderTotalRowData($balanceRow, true);

            if ($index < $savingsAccounts->count() - 1) {
                $this->newLine();
            }
        }
    }

    private function getSavingsAccountTitle(SavingsAccountReportData $account): string
    {
        $comment = trim($account->comment);
        $details = $comment === '' ? '' : $comment . ' ';

        return __('cli.report.presentation.title_savings_account_prefix')
            . ' '
            . $details
            . '(...' . $account->ibanLastThree . ')';
    }

    private function formatSavingsAmount(float $amount): string
    {
        return $amount === 0.0 ? '-' : $this->formatAmount($amount);
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
            $expenseRow[] = $this->formatAmount($period->totalOutgoing);
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

    /**
     * Render totals across all periods.
     *
     * @param Collection $reportData
     * @return void
     */
    private function renderTotalOverview(Collection $reportData): void
    {
        $totalIncome = $reportData->sum(fn (PeriodReportData $period) => $period->totalIncome);
        $totalOutgoing = $reportData->sum(fn (PeriodReportData $period) => $period->totalOutgoing);
        $totalBalance = $totalIncome - $totalOutgoing;

        $this->line(__('cli.report.presentation.title_total_overview'));
        $this->tableRenderer->renderSingleColumnTableSeparator();
        $this->tableRenderer->renderSingleColumnTableHeader(__('cli.report.presentation.label_total'));
        $this->tableRenderer->renderSingleColumnDataRow([
            __('cli.report.presentation.label_total_income'),
            $this->formatAmount($totalIncome),
        ]);
        $this->tableRenderer->renderSingleColumnDataRow([
            __('cli.report.presentation.label_total_expenses'),
            $this->formatAmount($totalOutgoing),
        ]);
        $this->tableRenderer->renderSingleColumnTableSeparator();
        $this->tableRenderer->renderSingleColumnTotalRowData([
            __('cli.report.presentation.label_total_balance'),
            $this->formatAmount($totalBalance),
        ]);
    }
}
