<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\Transactions;
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
     * @param boolean $considerIgnoreIbans
     * @return void
     */
    public function validateAll(BankAccount $bankAccount, bool $considerIgnoreIbans = true)
    {
        $query = Transactions::select('id');
        $this->validateTransactionAssignments($query, $bankAccount, $considerIgnoreIbans);
    }

    /**
     *
     * @param array $transactionIds
     * @param BankAccount $bankAccount
     * @param boolean $considerIgnoreIbans
     * @return void
     */
    public function validateRange(
        array $transactionIds,
        BankAccount $bankAccount,
        bool $considerIgnoreIbans = true
    ): void {
        $query = Transactions::select('id')->whereIn('id', $transactionIds);
        $this->validateTransactionAssignments($query, $bankAccount, $considerIgnoreIbans);
    }

    /**
     *
     * @param Builder $query
     * @param BankAccount $bankAccount
     * @param boolean $considerIgnoreIbans
     * @return void
     */
    private function validateTransactionAssignments(
        Builder $query,
        BankAccount $bankAccount,
        bool $considerIgnoreIbans
    ): void {
        $this->io->infoInLn(
            __('cli.rule.validator.validate_assignments', ['iban' => $bankAccount->iban, 'id' => $bankAccount->id])
        );
        $query->where('bank_account_iban', $bankAccount->iban);
        $ignoreIbans = IgnoreList::where('bank_account_id', $bankAccount->id)->select('value')->get();

        if ($considerIgnoreIbans && $ignoreIbans->isNotEmpty()) {
            $query->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        }
        $transactionRuleMap = [];
        $allRules = $this->ruleListService->getRulesWithExpression($bankAccount->id);

        foreach ($allRules as $rule) {
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
            $this->io->info( __('cli.rule.validator.validate_assignments_finished') );
        }
    }
}
