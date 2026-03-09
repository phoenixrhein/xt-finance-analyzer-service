<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Services\Rule\RefreshTransactionRuleIndexService;

class RefreshTransactionRuleIndex extends FinCommand
{
    use BankAccountIdParameter;

    /**
     *
     * @var RefreshTransactionRuleIndexService
     */
    protected RefreshTransactionRuleIndexService $indexService;

    /**
     *
     * @param RefreshTransactionRuleIndexService $indexService
     * @return void
     */
    public function init(RefreshTransactionRuleIndexService $indexService): void
    {
        $this->indexService = $indexService;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-refresh-index {accountId : [:cli.base.param.account_id:]} " .
        "{--considerIgnoreIbans : [:cli.base.param.consider_ignore_ibans:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.rule.refresh_index.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $considerIgnoreIbans = $this->option('considerIgnoreIbans');
        if (!$considerIgnoreIbans) {
            $this->emptyLn();
            $this->warn(__('cli.rule.refresh_index.not_considering_ignore_ibans'));
            $this->emptyLn();
        }

        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'), true);
        $this->indexService->refreshAll($bankAccount, $considerIgnoreIbans);
    }
}
