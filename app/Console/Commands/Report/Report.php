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
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

class Report extends FinCommand implements ProvidesAccountListQueryInterface
{
    use BankAccountIdParameter;

    /**
     *
     * @var AccountListQuery
     */
    protected AccountListQuery $accountListQuery;

    /**
     *
     * @var ReportConfiguratorWizard
     */
    protected ReportConfiguratorWizard $reportConfiguratorWizard;

    /**
     *
     * @var RuleTransactionPreparer
     */
    protected RuleTransactionPreparer $ruleTransactionPreparer;

    /**
     *
     * @var ReportDataProcessor
     */
    protected ReportDataProcessor $reportDataProcessor;

    /**
     *
     * @var ReportPresenter
     */
    protected ReportPresenter $reportPresenter;

    /**
     *
     * @param AccountListQuery $accountListQuery
     * @param ReportConfiguratorWizard $reportConfiguratorWizard
     * @param RuleTransactionPreparer $ruleTransactionPreparer
     * @param ReportDataProcessor $reportDataProcessor
     * @param ReportPresenter $reportPresenter
     */
    public function init(
        AccountListQuery $accountListQuery,
        ReportConfiguratorWizard $reportConfiguratorWizard,
        RuleTransactionPreparer $ruleTransactionPreparer,
        ReportDataProcessor $reportDataProcessor,
        ReportPresenter $reportPresenter
    ) {
        $this->accountListQuery = $accountListQuery;
        $this->reportConfiguratorWizard = $reportConfiguratorWizard;
        $this->ruleTransactionPreparer = $ruleTransactionPreparer;
        $this->reportDataProcessor = $reportDataProcessor;
        $this->reportPresenter = $reportPresenter;
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
     * @return AccountListQuery
     */
    public function getAccountListQuery(): AccountListQuery
    {
        return $this->accountListQuery;
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
