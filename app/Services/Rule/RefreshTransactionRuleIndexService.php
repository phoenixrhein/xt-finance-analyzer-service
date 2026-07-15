<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\Transactions;
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
     * @param boolean $considerIgnoreIbans
     * @return void
     */
    public function refreshAll(BankAccount $bankAccount, bool $considerIgnoreIbans = true): void
    {
        $this->io->info(__('cli.rule.refresh_index.starts'));
        $ignoreIbans = new Collection();
        DB::table('rule_transaction')
            ->where('bank_account_id', $bankAccount->id)
            ->delete();

        if (DB::table('rule_transaction')->count() === 0) {
            DB::table('rule_transaction')->truncate();
        }

        if ($considerIgnoreIbans) {
            $ignoreIbans = IgnoreList::where('bank_account_id', $bankAccount->id)->select('value')->get();
        }

        DB::transaction(function () use ($bankAccount, $ignoreIbans) {
            $this->io->emptyLn();
            $rules = $this->ruleListService->getRulesWithExpression($bankAccount->id);
            $total = count($rules);
            $updated = 0;
            $zero = 0;

            foreach ($rules as $rule) {
                $transactionIds = $this->addRuleTranscations($rule, $bankAccount, $ignoreIbans);

                $countStyle = $transactionIds->count() > 0 ? 'info' : 'error';
                $transactionIds->count() === 0 ? $zero++ : $updated++;
                $this->io->line(
                    '<info>' . $rule['name'] . '</info>' .
                    ' [<comment>' . $rule['expression'] . '</comment>]: ' .
                    "<{$countStyle}>" . count($transactionIds) . "</{$countStyle}>" .
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
     * @param Collection $ignoreIbans
     * @return SupportCollection
     */
    private function addRuleTranscations(
        array $rule,
        BankAccount $bankAccount,
        Collection $ignoreIbans,
    ): SupportCollection {
        $query = Transactions::select('id');
        $query->where('bank_account_iban', $bankAccount->iban);

        if ($ignoreIbans->isNotEmpty()) {
            $this->applyIgnoreIbans($query, $ignoreIbans);
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

    /**
     *
     * @param Builder $query
     * @param array $rule
     * @return void
     */
    private function applyCashflowFilter(Builder $query, array $rule): void
    {
        if ($this->isCashflowInRule($rule)) {
            $query->where('amount', '>=', 0);
        } else {
            $query->where('amount', '<', 0);
        }
    }

    /**
     *
     * @param array $rule
     * @return boolean
     */
    private function isCashflowInRule(array $rule): bool
    {
        $category = Category::find($rule['actions']['category_id']);
        return $category->in_category_id === $rule['actions']['category_id'];
    }

    /**
     *
     * @param Builder $query
     * @param Collection $ignoreIbans
     * @return void
     */
    private function applyIgnoreIbans(Builder $query, Collection $ignoreIbans): void
    {
        if ($ignoreIbans->isNotEmpty()) {
            $query->whereNotIn('creditor_iban', $ignoreIbans->toArray());
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
    }

    /**
     *
     * @param integer $ruleId
     * @param BankAccount $bankAccount
     * @param boolean $considerIgnoreIbans
     * @return void
     */
    public function addRule(int $ruleId, BankAccount $bankAccount, bool $considerIgnoreIbans = true): void
    {
        $ignoreIbans = new Collection();

        if ($considerIgnoreIbans) {
            $ignoreIbans = IgnoreList::where('bank_account_id', $bankAccount->id)->select('value')->get();
        }

        $rule = $this->ruleListService->getRuleWithExpression($ruleId);

        if (empty($rule)) {
            throw new \InvalidArgumentException('Rule with ID ' . $ruleId . ' does not exist.');
        }

        $this->addRuleTranscations($rule, $bankAccount, $ignoreIbans);
    }
}
