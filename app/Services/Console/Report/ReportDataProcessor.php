<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Enums\TransactionSplitType;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Dto\Report\CategoryNode;
use de\xovatec\financeAnalyzer\Dto\Report\PeriodReportData;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Processes financial data and prepares it for report display.
 * Aggregates transactions by category and period.
 */
class ReportDataProcessor
{
    private const CASH_PAYOUT_CATEGORY_ID = -1;

    /**
     * Process report data for the given periods.
     *
     * @param BankAccount $bankAccount The bank account to process
     * @param array<array{from: string, to: string}> $periods Array of period ranges with 'from' and 'to' date strings
     * @param bool $considerExclusionIbans Whether to exclude excluded IBANs
     * @return Collection<PeriodReportData>
     */
    public function process(BankAccount $bankAccount, array $periods, bool $considerExclusionIbans): Collection
    {
        $cashflow = $bankAccount->cashflow;

        if (!$cashflow) {
            return collect();
        }

        $reportData = collect($periods)->map(fn (array $period) =>
            $this->processPeriod(
                $bankAccount,
                Carbon::createFromFormat('Y-m-d', $period['from']),
                Carbon::createFromFormat('Y-m-d', $period['to']),
                $cashflow->in_category_id,
                $cashflow->out_category_id,
                $considerExclusionIbans
            ));

        return $this->addMissingCashPayoutNodes($reportData);
    }

    /**
     * Process a single period.
     */
    private function processPeriod(
        BankAccount $bankAccount,
        Carbon $start,
        Carbon $end,
        int $incomeCategoryId,
        int $outcomeCategoryId,
        bool $considerExclusionIbans
    ): PeriodReportData {
        $transactions = $this->loadTransactions($bankAccount, $start, $end, $considerExclusionIbans);
        // Load excluded IBAN transactions if needed for reporting
        $excludedIbanTransactions = collect();
        $excludedIbans = [];
        if ($considerExclusionIbans) {
            $excludedIbans = $bankAccount
                ->exclusionList()
                ->where('type', 'iban')
                ->pluck('value')
                ->toArray();

            if (!empty($excludedIbans)) {
                $excludedIbanTransactions = $bankAccount
                    ->transactions()
                    ->tap(fn (Builder $query) => $this->applyEffectiveDateFilter($query, $start, $end))
                    ->whereIn('creditor_iban', $excludedIbans)
                    ->get();
            }
        }

        // Separate transactions by income/expense
        $incomingTransactions = $transactions->filter(fn (Transactions $t) => $t->amount > 0);
        $outgoingTransactions = $transactions->filter(fn (Transactions $t) => $t->amount < 0);

        // Build category trees
        $incomingTree = $this->buildCategoryTree(
            $incomingTransactions,
            $incomeCategoryId,
            'Unzugeordnet'
        );
        $outgoingTree = $this->buildCategoryTree(
            $outgoingTransactions,
            $outcomeCategoryId,
            'Unzugeordnet'
        );

        // Calculate totals
        $totalIncome = $incomingTransactions->sum('amount');
        $totalOutgoing = abs($outgoingTransactions->sum('amount'));

        // Calculate excluded IBAN transfers
        $toExcludedIban = $excludedIbanTransactions->filter(fn (Transactions $t) => $t->amount < 0)->sum('amount');
        $fromExcludedIban = $excludedIbanTransactions->filter(fn (Transactions $t) => $t->amount > 0)->sum('amount');

        return new PeriodReportData(
            $start,
            $end,
            $incomingTree,
            $outgoingTree,
            $totalIncome,
            $totalOutgoing,
            $totalIncome - $totalOutgoing,
            $toExcludedIban,
            $fromExcludedIban
        );
    }

    /**
     * Load transactions for a specific period.
     */
    private function loadTransactions(
        BankAccount $bankAccount,
        Carbon $start,
        Carbon $end,
        bool $considerExclusionIbans
    ): Collection {
        $query = $bankAccount
            ->transactions()
            ->tap(fn (Builder $query) => $this->applyEffectiveDateFilter($query, $start, $end))
            ->with('transactionSplit')
            ->select('transactions.*');

        if ($considerExclusionIbans) {
            $excludedIbans = $bankAccount
                ->exclusionList()
                ->where('type', 'iban')
                ->pluck('value')
                ->toArray();

            if (!empty($excludedIbans)) {
                $query->whereNotIn('creditor_iban', $excludedIbans);
            }
        }

        return $query->get();
    }

    /**
     * Filter transactions by their effective report date.
     *
     * An active adjustment replaces the original transaction date for reports.
     */
    private function applyEffectiveDateFilter(Builder $query, Carbon $start, Carbon $end): Builder
    {
        $from = $start->format('Y-m-d');
        $to = $end->format('Y-m-d');

        return $query->where(function (Builder $query) use ($from, $to) {
            $query
                ->where(function (Builder $query) use ($from, $to) {
                    $query
                        ->whereNotExists(function ($adjustments) {
                            $adjustments
                                ->from('transaction_adjustment')
                                ->whereColumn(
                                    'transaction_adjustment.transaction_id',
                                    'transactions.id'
                                )
                                ->whereNull('transaction_adjustment.deleted_at');
                        })
                        ->whereBetween('transactions.transaction_date', [$from, $to]);
                })
                ->orWhereExists(function ($adjustments) use ($from, $to) {
                    $adjustments
                        ->from('transaction_adjustment')
                        ->whereColumn(
                            'transaction_adjustment.transaction_id',
                            'transactions.id'
                        )
                        ->whereNull('transaction_adjustment.deleted_at')
                        ->whereBetween('transaction_adjustment.transaction_date', [$from, $to]);
                });
        });
    }

    /**
     * Build category tree with aggregated transaction amounts.
     */
    private function buildCategoryTree(
        Collection $transactions,
        int $rootCategoryId,
        string $unassignedLabel
    ): Collection {
        // Load root category (eager loading will happen in buildCategoryNode)
        $rootCategory = Category::findOrFail($rootCategoryId);

        // Group transactions by assigned category
        $transactionsByCategory = $this->groupTransactionsByCategory($transactions);

        // Build tree recursively
        $rootNode = collect([$rootCategory])
            ->map(fn (Category $category) =>
                $this->buildCategoryNode(
                    $category,
                    $transactionsByCategory,
                    $unassignedLabel
                ))
            ->first();

        // Add unassigned category if needed
        $children = $rootNode->children->toArray();
        if (
            $rootNode->hasAmount()
            || $rootNode->children->isNotEmpty()
            || ($transactionsByCategory['_unassigned'] ?? 0.0) !== 0.0
            || ($transactionsByCategory[self::CASH_PAYOUT_CATEGORY_ID] ?? 0.0) !== 0.0
        ) {
            $children[] = new CategoryNode(
                null,
                $unassignedLabel,
                $transactionsByCategory['_unassigned'] ?? 0.0,
                0.0,
                collect(),
                0
            );
        }

        if (($transactionsByCategory[self::CASH_PAYOUT_CATEGORY_ID] ?? 0.0) !== 0.0) {
            $children[] = new CategoryNode(
                self::CASH_PAYOUT_CATEGORY_ID,
                __('cli.report.presentation.label_cash'),
                $transactionsByCategory[self::CASH_PAYOUT_CATEGORY_ID],
                0.0,
                collect(),
                0
            );
        }

        // Return collection with all direct children of root
        return collect($children)->filter(fn (CategoryNode $node) => $node->hasAmount());
    }

    /**
     * Build a category node with its children.
     */
    private function buildCategoryNode(
        Category $category,
        array $transactionsByCategory,
        string $unassignedLabel,
        int $depth = 0
    ): CategoryNode {
        $directAmount = $transactionsByCategory[$category->id] ?? 0.0;
        $childrenAmount = 0.0;

        // Always load subcategories (ensures they're available)
        $category->load('subCategories');

        $subCategories = $category->subCategories ?? collect();

        // Build all child nodes first
        $allChildren = $subCategories
            ->map(function (Category $child) use ($transactionsByCategory, $unassignedLabel, $depth) {
                return $this->buildCategoryNode(
                    $child,
                    $transactionsByCategory,
                    $unassignedLabel,
                    $depth + 1
                );
            })
            ->values();

        // Separate children into two groups: those with amounts and those without
        $childrenWithAmount = $allChildren->filter(fn (CategoryNode $node) => $node->hasAmount())->values();
        $childrenWithoutAmount = $allChildren->filter(fn (CategoryNode $node) => !$node->hasAmount())->values();

        // Only include children that have amounts themselves OR those that are parents of children with amounts
        // Children without amounts that have no children can be filtered out
        $children = $childrenWithAmount->merge(
            $childrenWithoutAmount->filter(fn (CategoryNode $node) => $node->children->isNotEmpty())
        )->values();

        // Calculate children amount from all children (including those with and without direct amounts)
        foreach ($children as $child) {
            $childrenAmount += $child->getTotalAmount();
        }

        return new CategoryNode(
            $category->id,
            $category->name,
            $directAmount,
            $childrenAmount,
            $children,
            $depth
        );
    }

    /**
     * Group transactions by their assigned category.
     * Returns array with category_id => sum, and '_unassigned' => sum for unassigned transactions.
     */
    private function groupTransactionsByCategory(Collection $transactions): array
    {
        $transactionIds = $transactions->pluck('id')->toArray();

        if (empty($transactionIds)) {
            return [];
        }

        // Query to get transaction -> category mappings via rules/actions
        $mappings = DB::table('transactions')
            ->whereIn('transactions.id', $transactionIds)
            ->leftJoin('rule_transaction', 'transactions.id', '=', 'rule_transaction.transaction_id')
            ->leftJoin('rule', 'rule_transaction.rule_id', '=', 'rule.id')
            ->leftJoin('action', 'rule.id', '=', 'action.rule_id')
            ->select('transactions.id', 'transactions.amount', 'action.category_id')
            ->get();

        $grouped = [];

        $transactionsById = $transactions->keyBy('id');

        foreach ($mappings->groupBy('id') as $transactionId => $transactionMappings) {
            $transaction = $transactionsById->get($transactionId);
            if (!$transaction instanceof Transactions) {
                continue;
            }

            $categoryId = $transactionMappings->first()->category_id ?? '_unassigned';
            $splitAmounts = $this->calculateSplitAmounts($transaction);
            $remainingAmount = $splitAmounts['remaining'];

            $grouped[$categoryId] = ($grouped[$categoryId] ?? 0.0) + max(0.0, $remainingAmount);

            $cashPayoutAmount = $splitAmounts['cash_payout'];
            if ($cashPayoutAmount > 0.0) {
                $grouped[self::CASH_PAYOUT_CATEGORY_ID] =
                    ($grouped[self::CASH_PAYOUT_CATEGORY_ID] ?? 0.0) + $cashPayoutAmount;
            }

            $otherAmount = $splitAmounts['other'];
            if ($otherAmount > 0.0) {
                $grouped['_unassigned'] = ($grouped['_unassigned'] ?? 0.0) + $otherAmount;
            }
        }

        return $grouped;
    }

    /**
     * Calculate the report amounts for one transaction and its splits.
     *
     * @param Transactions $transaction
     * @return array{remaining: float, cash_payout: float, other: float}
     */
    public function calculateSplitAmounts(Transactions $transaction): array
    {
        $amounts = [
            'remaining' => abs((float) $transaction->amount),
            'cash_payout' => 0.0,
            'other' => 0.0,
        ];

        foreach ($transaction->transactionSplit as $split) {
            $splitAmount = (float) $split->amount;
            $amounts['remaining'] -= $splitAmount;
            $type = $split->type?->value ?? TransactionSplitType::OTHER->value;
            if ($type === TransactionSplitType::CASH_PAYOUT->value) {
                $amounts['cash_payout'] += $splitAmount;
            } else {
                $amounts['other'] += $splitAmount;
            }
        }

        $amounts['remaining'] = max(0.0, round($amounts['remaining'], 2));

        return $amounts;
    }

    /**
     * Keep the synthetic cash category available as a reference across periods.
     *
     * @param Collection<PeriodReportData> $reportData
     * @return Collection<PeriodReportData>
     */
    private function addMissingCashPayoutNodes(Collection $reportData): Collection
    {
        $hasCashPayout = $reportData->contains(
            fn (PeriodReportData $period) =>
                $period->incomingCategories->contains(
                    fn (CategoryNode $category) => $category->categoryId === self::CASH_PAYOUT_CATEGORY_ID
                )
                || $period->outgoingCategories->contains(
                    fn (CategoryNode $category) => $category->categoryId === self::CASH_PAYOUT_CATEGORY_ID
                )
        );

        if (!$hasCashPayout) {
            return $reportData;
        }

        return $reportData->map(function (PeriodReportData $period): PeriodReportData {
            $cashNode = new CategoryNode(
                self::CASH_PAYOUT_CATEGORY_ID,
                __('cli.report.presentation.label_cash'),
                0.0,
                0.0,
                collect(),
                0
            );

            $incomingCategories = $period->incomingCategories;
            if (!$incomingCategories->contains(
                fn (CategoryNode $category) => $category->categoryId === self::CASH_PAYOUT_CATEGORY_ID
            )) {
                $incomingCategories = $incomingCategories->push(clone $cashNode);
            }

            $outgoingCategories = $period->outgoingCategories;
            if (!$outgoingCategories->contains(
                fn (CategoryNode $category) => $category->categoryId === self::CASH_PAYOUT_CATEGORY_ID
            )) {
                $outgoingCategories = $outgoingCategories->push(clone $cashNode);
            }

            return new PeriodReportData(
                $period->periodStart,
                $period->periodEnd,
                $incomingCategories,
                $outgoingCategories,
                $period->totalIncome,
                $period->totalOutgoing,
                $period->balance,
                $period->toExcludedIban,
                $period->fromExcludedIban
            );
        });
    }
}
