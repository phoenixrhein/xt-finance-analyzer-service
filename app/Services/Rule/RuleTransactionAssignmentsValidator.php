<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\ExclusionList;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;
use de\xovatec\financeAnalyzer\Enums\RuleTargetType;
use de\xovatec\financeAnalyzer\Enums\TransactionSplitType;
use de\xovatec\financeAnalyzer\Services\Console\Output\ConsoleOutputInterface;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\Rule\RuleListService;
use de\xovatec\financeAnalyzer\Services\Rule\RuleToConditionTransformer;
use Illuminate\Database\Eloquent\Builder;

class RuleTransactionAssignmentsValidator
{
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
    public function validateAll(BankAccount $bankAccount, bool $considerExclusionIbans = true)
    {
        $query = Transactions::select('id');
        $this->validateTransactionAssignments($query, $bankAccount, $considerExclusionIbans);
        $this->validateSplitAssignments($bankAccount, $considerExclusionIbans);
    }

    /**
     *
     * @param array $transactionIds
     * @param BankAccount $bankAccount
     * @param boolean $considerExclusionIbans
     * @return void
     */
    public function validateRange(
        array $transactionIds,
        BankAccount $bankAccount,
        bool $considerExclusionIbans = true
    ): void {
        $query = Transactions::select('id')->whereIn('id', $transactionIds);
        $this->validateTransactionAssignments($query, $bankAccount, $considerExclusionIbans);
        $this->validateSplitAssignments($bankAccount, $considerExclusionIbans, $transactionIds);
    }

    /**
     *
     * @param Builder $query
     * @param BankAccount $bankAccount
     * @param boolean $considerExclusionIbans
     * @return void
     */
    private function validateTransactionAssignments(
        Builder $query,
        BankAccount $bankAccount,
        bool $considerExclusionIbans
    ): void {
        $this->io->infoInLn(
            __('cli.rule.validator.validate_assignments', ['iban' => $bankAccount->iban, 'id' => $bankAccount->id])
        );
        $query->where('bank_account_iban', $bankAccount->iban);
        $exclusionIbans = ExclusionList::where('bank_account_id', $bankAccount->id)->select('value')->get();

        if ($considerExclusionIbans && $exclusionIbans->isNotEmpty()) {
            $query->whereNotIn('creditor_iban', $exclusionIbans->toArray());
        }
        $transactionRuleMap = [];
        $allRules = $this->ruleListService->getRulesWithExpression($bankAccount->id);

        foreach ($allRules as $rule) {
            if (($rule['target_type'] ?? RuleTargetType::TRANSACTION->value) !== RuleTargetType::TRANSACTION->value) {
                continue;
            }
            $ruleQuery = CopyBuilderQueryHelper::copy($query);

            $this->sqlQueryBuilder->build(
                $ruleQuery,
                $this->transformer->transformToConditionList($rule['condition_link'])
            );

            $transactionIds = $ruleQuery->pluck('id');

            foreach ($transactionIds as $tid) {
                $transactionRuleMap[$tid][] = $rule['id'];
            }
        }

        $hasErrors = false;
        foreach ($transactionRuleMap as $tid => $ruleIds) {
            if (count($ruleIds) > 1) {
                if (!$hasErrors) {
                    $this->io->emptyLn();
                    $hasErrors = true;
                }
                $this->io->error(
                    __(
                        'cli.rule.validator.validate_assignments_overlaps',
                        ['id' => $tid, 'ruleIds' => implode(',', $ruleIds)]
                    )
                );
            }
        }

        if (!$hasErrors) {
            $this->io->info(__('cli.rule.validator.validate_assignments_finished'));
        }
    }

    private function validateSplitAssignments(
        BankAccount $bankAccount,
        bool $considerExclusionIbans,
        ?array $transactionIds = null
    ): void {
        $query = TransactionSplit::query()
            ->join('transactions', 'transaction_split.transaction_id', '=', 'transactions.id')
            ->where('transactions.bank_account_iban', $bankAccount->iban)
            ->where('transaction_split.type', TransactionSplitType::OTHER->value);

        if ($transactionIds !== null) {
            $query->whereIn('transaction_split.transaction_id', $transactionIds);
        }

        $exclusionIbans = ExclusionList::where('bank_account_id', $bankAccount->id)->select('value')->get();
        if ($considerExclusionIbans && $exclusionIbans->isNotEmpty()) {
            $query->whereNotIn('transactions.creditor_iban', $exclusionIbans->toArray());
        }

        $splitRuleMap = [];
        foreach ($this->ruleListService->getRulesWithExpression($bankAccount->id) as $rule) {
            if (($rule['target_type'] ?? RuleTargetType::TRANSACTION->value) !== RuleTargetType::TRANSACTION_SPLIT->value) {
                continue;
            }

            $ruleQuery = CopyBuilderQueryHelper::copy($query);
            $this->sqlQueryBuilder->build(
                $ruleQuery,
                $this->transformer->transformToConditionList($rule['condition_link'])
            );

            $category = \de\xovatec\financeAnalyzer\Models\Category::find($rule['actions']['category_id']);
            $cashflow = $category->getCashflow();
            $cashflowCategory = $category->getCashflowCategory();
            $amountOperator = $cashflow->in_category_id === $cashflowCategory->id ? '>=' : '<';
            $splitIds = $ruleQuery->where('transactions.amount', $amountOperator, 0)
                ->pluck('transaction_split.id');

            foreach ($splitIds as $splitId) {
                $splitRuleMap[$splitId][] = $rule['id'];
            }
        }

        foreach ($splitRuleMap as $splitId => $ruleIds) {
            if (count($ruleIds) > 1) {
                $this->io->error(
                    __(
                        'cli.rule.validator.validate_assignments_overlaps',
                        ['id' => $splitId, 'ruleIds' => implode(',', $ruleIds)]
                    )
                );
            }
        }
    }
}
