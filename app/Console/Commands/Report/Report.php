<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Report;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class Report extends FinCommand
{
    use BankAccountIdParameter;

    /**
     *
     * @param AccountListQuery $accountlistQuery
     */
    public function __construct(private AccountListQuery $accountlistQuery)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:report {accountId? : [:cli.base.param.account_id:]}';

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
    protected function process(): void
    {
        $account = ($this->getBankAccount(
            $this->argument('accountId') ? (int) $this->argument('accountId') : null,
            true,
            true
        ));

        if (!$account instanceof BankAccount) {
            return;
        }
    }
}
