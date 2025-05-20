<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Services\Rule\RefreshTransactionRuleIndexService;

class RefreshTransactionRuleIndex extends FinCommand
{
    use BankAccountIdParameter;

    public function __construct(private RefreshTransactionRuleIndexService $indexService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-refresh-index {accountId : [:cli.base.param.account_id:]} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.rule.refresh_index.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'), true);
        $this->indexService->refreshAll($bankAccount->id);
    }
}
