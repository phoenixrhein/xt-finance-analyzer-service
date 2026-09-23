<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Models\ExclusionList;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;
use de\xovatec\financeAnalyzer\Enums\RuleTargetType;
use de\xovatec\financeAnalyzer\Enums\TransactionSplitType;
use de\xovatec\financeAnalyzer\Services\Console\Output\ConsoleOutputInterface;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\Rule\RuleListService;
use de\xovatec\financeAnalyzer\Services\Rule\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Traits\Utils\CategoryPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class RefreshTransactionRuleIndexService
{
    use CategoryPath;

    /**
     *
     * @param SqlQueryBuilder $sqlQueryBuilder
     * @param RuleToConditionTransformer $transformer
     * @param RuleListService $ruleListService
     * @param ConsoleOutputInterface $io
     */
    public function __construct(
        private SqlQueryBuilder $sqlQueryBuilder,
        private RuleToConditionTransformer $transformer,
        private RuleListService $ruleListService,
        private ConsoleOutputInterface $io
    ) {
    }

    /**
     *
     * @param BankAccount $bankAccount
     * @param boolean $considerExclusionIbans
     * @return void
     */
    public function refreshAll(BankAccount $bankAccount, bool $considerExclusionIbans = true): void
    {
        $this->io->info(__('cli.rule.refresh_index.starts'));
        $exclusionIbans = new Collection();
        DB::table('rule_transaction')
            ->where('bank_account_id', $bankAccount->id)
            ->delete();

        if (DB::table('rule_transaction')->count() === 0) {
            DB::table('rule_transaction')->truncate();
        }
        DB::table('rule_transaction_split')
            ->where('bank_account_id', $bankAccount->id)
            ->delete();

        if ($considerExclusionIbans) {
            $exclusionIbans = ExclusionList::where('bank_account_id', $bankAccount->id)->select('value')->get();
        }

        DB::transaction(function () use ($bankAccount, $exclusionIbans) {
            $this->io->emptyLn();
            $rules = $this->ruleListService->getRulesWithExpression($bankAccount->id);
            $total = count($rules);
            $updated = 0;
            $zero = 0;

            foreach ($rules as $rule) {
                $targetIds = $this->addRuleTargets($rule, $bankAccount, $exclusionIbans);

                $countStyle = $targetIds->count() > 0 ? 'info' : 'error';
                $targetIds->count() === 0 ? $zero++ : $updated++;
                $this->io->line(
                    '<info>' . $rule['name'] . '</info>' .
                    ' [<comment>' . $rule['expression'] . '</comment>]: ' .
                    "<{$countStyle}>" . count($targetIds) . "</{$countStyle}>" .
                    ' -> ' . $this->buildPathAsString(Category::find($rule['actions']['category_id']))
                );
            }

            $this->io->emptyLn();

            $this->io->question(__('cli.rule.refresh_index.summary', [
                'total' => $total,
                'updated' => $updated,
                'zero' => $zero,
            ]));
        });
    }

    /**
     *
     * @param array $rule
     * @param BankAccount $bankAccount
     * @param Collection $exclusionIbans
     * @return SupportCollection
     */
    private function addRuleTranscations(
        array $rule,
        BankAccount $bankAccount,
        Collection $exclusionIbans,
    ): SupportCollection {
        $query = Transactions::select('id');
        $query->where('bank_account_iban', $bankAccount->iban);

        if ($exclusionIbans->isNotEmpty()) {
            $this->applyExclusionIbans($query, $exclusionIbans);
        }

        $this->applyCashflowFilter($query, $rule);

        $this->sqlQueryBuilder->build(
            $query,
            $this->transformer->transformToConditionList($rule['condition_link'])
        );

        $transactionIds = $query->pluck('id');

        $insertData = $transactionIds->map(fn($transactionId) => [
            'rule_id' => $rule['id'],
            'transaction_id' => $transactionId,
            'bank_account_id' => $bankAccount->id,
        ])->toArray();


        foreach (array_chunk($insertData, 5000) as $chunkData) {
            DB::table('rule_transaction')->insert($chunkData);
        }

        return $transactionIds;
    }

    private function addRuleTargets(array $rule, BankAccount $bankAccount, Collection $exclusionIbans): SupportCollection
    {
        $targetType = RuleTargetType::tryFrom($rule['target_type'] ?? RuleTargetType::TRANSACTION->value)
            ?? RuleTargetType::TRANSACTION;

        if ($targetType === RuleTargetType::TRANSACTION_SPLIT) {
            return $this->addRuleTransactionSplits($rule, $bankAccount, $exclusionIbans);
        }

        return $this->addRuleTranscations($rule, $bankAccount, $exclusionIbans);
    }

    private function addRuleTransactionSplits(
        array $rule,
        BankAccount $bankAccount,
        Collection $exclusionIbans
    ): SupportCollection {
        $query = TransactionSplit::query()
            ->join('transactions', 'transaction_split.transaction_id', '=', 'transactions.id')
            ->where('transactions.bank_account_iban', $bankAccount->iban)
            ->where('transaction_split.type', TransactionSplitType::OTHER->value)
            ->whereNull('transaction_split.deleted_at');

        if ($exclusionIbans->isNotEmpty()) {
            $this->applyExclusionIbans($query, $exclusionIbans);
        }

        $this->applyCashflowFilter($query, $rule);
        $this->sqlQueryBuilder->build(
            $query,
            $this->transformer->transformToConditionList($rule['condition_link'])
        );

        $splitIds = $query->pluck('transaction_split.id');
        $insertData = $splitIds->map(fn ($splitId) => [
            'rule_id' => $rule['id'],
            'transaction_split_id' => $splitId,
            'bank_account_id' => $bankAccount->id,
        ])->toArray();

        foreach (array_chunk($insertData, 5000) as $chunkData) {
            DB::table('rule_transaction_split')->insert($chunkData);
        }

        return $splitIds;
    }

    /**
     *
     * @param Builder $query
     * @param array $rule
     * @return void
     */
    private function applyCashflowFilter(Builder $query, array $rule): void
    {
        if ($this->isCashflowInRule($rule)) {
            $query->where($query->getQuery()->joins ? 'transactions.amount' : 'amount', '>=', 0);
        } else {
            $query->where($query->getQuery()->joins ? 'transactions.amount' : 'amount', '<', 0);
        }
    }

    /**
     *
     * @param array $rule
     * @return boolean
     */
    private function isCashflowInRule(array $rule): bool
    {
        /** @var Category $category */
        $category = Category::find($rule['actions']['category_id']);
        $cashflow = $category->getCashflow();
        $cashflowCategory = $category->getCashflowCategory();

        return $cashflow->in_category_id === $cashflowCategory->id;
    }

    /**
     *
     * @param Builder $query
     * @param Collection $exclusionIbans
     * @return void
     */
    private function applyExclusionIbans(Builder $query, Collection $exclusionIbans): void
    {
        if ($exclusionIbans->isNotEmpty()) {
            $query->whereNotIn('creditor_iban', $exclusionIbans->toArray());
        }
    }

    /**
     *
     * @param integer $ruleId
     * @param integer $bankAccountId
     * @return void
     */
    public function deleteByRuleId(int $ruleId, int $bankAccountId): void
    {
        DB::table('rule_transaction')
            ->where('rule_id', $ruleId)
            ->where('bank_account_id', $bankAccountId)
            ->delete();
        DB::table('rule_transaction_split')
            ->where('rule_id', $ruleId)
            ->where('bank_account_id', $bankAccountId)
            ->delete();
    }

    /**
     *
     * @param integer $ruleId
     * @param BankAccount $bankAccount
     * @param boolean $considerExclusionIbans
     * @return void
     */
    public function addRule(int $ruleId, BankAccount $bankAccount, bool $considerExclusionIbans = true): void
    {
        $exclusionIbans = new Collection();

        if ($considerExclusionIbans) {
            $exclusionIbans = ExclusionList::where('bank_account_id', $bankAccount->id)->select('value')->get();
        }

        $rule = $this->ruleListService->getRuleWithExpression($ruleId);

        if (empty($rule)) {
            throw new \InvalidArgumentException('Rule with ID ' . $ruleId . ' does not exist.');
        }

        $this->addRuleTargets($rule, $bankAccount, $exclusionIbans);
    }
}
