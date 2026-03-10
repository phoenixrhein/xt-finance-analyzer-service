<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Dto\Report\CategoryNode;
use de\xovatec\financeAnalyzer\Dto\Report\PeriodReportData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Processes financial data and prepares it for report display.
 * Aggregates transactions by category and period.
 */
class ReportDataProcessor
{
    /**
     * Process report data for the given periods.
     *
     * @param BankAccount $bankAccount The bank account to process
     * @param array<array{from: string, to: string}> $periods Array of period ranges with 'from' and 'to' date strings
     * @param bool $considerIgnoreIbans Whether to exclude ignored IBANs
     * @return Collection<PeriodReportData>
     */
    public function process(BankAccount $bankAccount, array $periods, bool $considerIgnoreIbans): Collection
    {
        $cashflow = $bankAccount->cashflow;

        if (!$cashflow) {
            return collect();
        }

        return collect($periods)->map(fn (array $period) =>
            $this->processPeriod(
                $bankAccount,
                Carbon::createFromFormat('Y-m-d', $period['from']),
                Carbon::createFromFormat('Y-m-d', $period['to']),
                $cashflow->in_category_id,
                $cashflow->out_category_id,
                $considerIgnoreIbans
            ));
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
        bool $considerIgnoreIbans
    ): PeriodReportData {
        $transactions = $this->loadTransactions($bankAccount, $start, $end, $considerIgnoreIbans);

        // Load ignored IBAN transactions if needed for reporting
        $ignoredIbanTransactions = collect();
        $ignoredIbans = [];
        if ($considerIgnoreIbans) {
            $ignoredIbans = $bankAccount
                ->ignoreList()
                ->where('type', 'iban')
                ->pluck('value')
                ->toArray();

            if (!empty($ignoredIbans)) {
                $ignoredIbanTransactions = $bankAccount
                    ->transactions()
                    ->whereBetween('transaction_date', [$start, $end])
                    ->whereIn('creditor_iban', $ignoredIbans)
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

        // Calculate ignored IBAN transfers
        $toIgnoredIban = $ignoredIbanTransactions->filter(fn (Transactions $t) => $t->amount < 0)->sum('amount');
        $fromIgnoredIban = $ignoredIbanTransactions->filter(fn (Transactions $t) => $t->amount > 0)->sum('amount');

        return new PeriodReportData(
            $start,
            $end,
            $incomingTree,
            $outgoingTree,
            $totalIncome,
            $totalOutgoing,
            $totalIncome - $totalOutgoing,
            $toIgnoredIban,
            $fromIgnoredIban
        );
    }

    /**
     * Load transactions for a specific period.
     */
    private function loadTransactions(
        BankAccount $bankAccount,
        Carbon $start,
        Carbon $end,
        bool $considerIgnoreIbans
    ): Collection {
        $query = $bankAccount
            ->transactions()
            ->whereBetween('transaction_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->select('transactions.*');

        if ($considerIgnoreIbans) {
            $ignoredIbans = $bankAccount
                ->ignoreList()
                ->where('type', 'iban')
                ->pluck('value')
                ->toArray();

            if (!empty($ignoredIbans)) {
                $query->whereNotIn('creditor_iban', $ignoredIbans);
            }
        }

        return $query->get();
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
        if ($rootNode->hasAmount() || $rootNode->children->isNotEmpty()) {
            $children[] = new CategoryNode(
                null,
                $unassignedLabel,
                $transactionsByCategory['_unassigned'] ?? 0.0,
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

        foreach ($mappings as $mapping) {
            $categoryId = $mapping->category_id ?? '_unassigned';
            $grouped[$categoryId] = ($grouped[$categoryId] ?? 0.0) + $mapping->amount;
        }

        return $grouped;
    }
}
