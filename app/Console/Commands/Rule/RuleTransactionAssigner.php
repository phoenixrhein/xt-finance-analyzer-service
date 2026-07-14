<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\Console\Rule\RuleAssignerWorkflow;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class RuleTransactionAssigner extends FinCommand
{
    use BankAccountIdParameter;

    /**
     *
     * @var RuleAssignerWorkflow
     */
    private RuleAssignerWorkflow $ruleAssignerWorkflow;

    /**
     *
     * @param RuleAssignerWorkflow $ruleAssignerWorkflow
     * @return void
     */
    public function init(
        RuleAssignerWorkflow $ruleAssignerWorkflow,
    ): void {
        $this->ruleAssignerWorkflow = $ruleAssignerWorkflow;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-assign {accountId : [:cli.base.param.account_id:]} ' .
        '{--range= : [:cli.param.date_range.description:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.rule.assign.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'), true);
        $this->ruleAssignerWorkflow->assignRule($bankAccount, $this);
    }


}
