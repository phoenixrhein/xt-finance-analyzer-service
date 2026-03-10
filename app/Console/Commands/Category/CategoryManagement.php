<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Services\Console\Category\ManageConsoleService;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

class CategoryManagement extends FinCommand implements ProvidesAccountListQueryInterface
{
    use BankAccountIdParameter;

    /**
     *
     * @var AccountListQuery
     */
    protected AccountListQuery $accountListQuery;

    /**
     *
     * @var ManageConsoleService
     */
    protected ManageConsoleService $manageConsoleService;

    /**
     *
     * @param AccountListQuery $accountListQuery
     */
    public function init(
        AccountListQuery $accountListQuery,
        ManageConsoleService $manageConsoleService
    ): void {
        $this->accountListQuery = $accountListQuery;
        $this->manageConsoleService = $manageConsoleService;
    }

    /**
     *
     * @return AccountListQuery
     */
    public function getAccountListQuery(): AccountListQuery
    {
        return $this->accountListQuery;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-mgmt {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.category.manage.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $this->manageConsoleService->manage(
            ($this->getBankAccount((int)$this->argument('accountId'), true))->id,
            __('cli.category.manage.action.finish')
        );
    }
}
