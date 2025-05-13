<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Services\Console\Category\ManageConsoleService;
use de\xovatec\financeAnalyzer\Services\Console\Category\CategoryConsoleService;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

class CategoryManagement extends FinCommand implements ProvidesAccountListQueryInterface
{
    use BankAccountIdParameter;

    /**
     *
     * @param AccountListQuery $accountListQuery
     * @param CategoryConsoleService $categoryConsoleService
     */
    public function __construct(
        private AccountListQuery $accountListQuery,
        private ManageConsoleService $manageConsoleService
    ) {
        parent::__construct();
        $this->manageConsoleService->setIo($this);
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
    protected function process(): void
    {
        $accountId = ($this->getBankAccount((int)$this->argument('accountId'), true))->id;

        $this->manageConsoleService->manage($accountId, __('cli.category.manage.action.finish'));
    }
}
