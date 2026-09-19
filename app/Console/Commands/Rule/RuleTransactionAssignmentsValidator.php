<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\Rule\RuleTransactionAssignmentsValidator
    as RuleTransactionAssignmentsValidatorService;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class RuleTransactionAssignmentsValidator extends FinCommand
{
    use BankAccountIdParameter;

    /**
     *
     * @var RuleTransactionAssignmentsValidatorService
     */
    protected RuleTransactionAssignmentsValidatorService $validator;

    /**
     *
     * @param RuleTransactionAssignmentsValidatorService $validator
     */
    public function init(RuleTransactionAssignmentsValidatorService $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-validate {accountId : [:cli.base.param.account_id:]} " .
        "{--considerExclusionIbans : [:cli.base.param.consider_exclusion_ibans:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.rule.validator.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $considerExclusionIbans = $this->option('considerExclusionIbans');
        if (!$considerExclusionIbans) {
            $this->emptyLn();
            $this->warn(__('cli.rule.validator.not_considering_exclusion_ibans'));
        }

        $this->emptyLn();
        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'), true);
        $this->validator->validateAll($bankAccount, $considerExclusionIbans);
    }
}
