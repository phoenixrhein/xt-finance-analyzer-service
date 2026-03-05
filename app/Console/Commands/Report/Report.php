<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Report;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportConfiguratorWizard;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportDataProcessor;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportPresenter;
use de\xovatec\financeAnalyzer\Services\Console\Report\RuleTransactionPreparer;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class Report extends FinCommand
{
    use BankAccountIdParameter;

    /**
     *
     * @param AccountListQuery $accountlistQuery
     * @param ReportConfiguratorWizard $reportConfiguratorWizard
     * @param RuleTransactionPreparer $ruleTransactionPreparer
     * @param ReportDataProcessor $reportDataProcessor
     * @param ReportPresenter $reportPresenter
     */
    public function __construct(
        private AccountListQuery $accountlistQuery,
        private ReportConfiguratorWizard $reportConfiguratorWizard,
        private RuleTransactionPreparer $ruleTransactionPreparer,
        private ReportDataProcessor $reportDataProcessor,
        private ReportPresenter $reportPresenter
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:report {accountId? : [:cli.base.param.account_id:]}' .
        '{--considerIgnoreIbans : [:cli.base.param.consider_ignore_ibans:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.report.description';

    /**
     *
     * @return AccountListQuery|null
     */
    protected function getAccountListQuery(): ?AccountListQuery
    {
        return $this->accountlistQuery;
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $account = ($this->getBankAccount(
            $this->argument('accountId') ? (int) $this->argument('accountId') : null,
            true,
            true
        ));

        if (!$account instanceof BankAccount) {
            return;
        }

        $periods = $this->reportConfiguratorWizard->runWizard($account);
        $this->ruleTransactionPreparer->prepareForReport($account, $this->option('considerIgnoreIbans'));

        $this->emptyLn();
        $this->emptyLn();

        $reportData = $this->reportDataProcessor->process(
            $account,
            $periods,
            !(bool) $this->option('considerIgnoreIbans')
        );

        $this->reportPresenter->render($reportData, $account->cashflow?->timespanType ?? null);
    }
}
