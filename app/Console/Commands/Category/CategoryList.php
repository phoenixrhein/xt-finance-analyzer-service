<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Services\Console\Category\TreeViewConsoleService;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

class CategoryList extends FinCommand implements ProvidesAccountListQueryInterface
{
    use BankAccountIdParameter;

    /**
     *
     * @param AccountListQuery $accountListQuery
     * @param TreeViewConsoleService $treeViewConsoleService
     */
    public function __construct(
        private AccountListQuery $accountListQuery,
        private TreeViewConsoleService $treeViewConsoleService
    ) {
        parent::__construct();
        $this->treeViewConsoleService->setIo($this);
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
    protected $signature = 'fin:cat-list {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.category.list.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $accountId = ($this->getBankAccount((int)$this->argument('accountId'), true))->id;

        $cashflow = Cashflow::where('bank_account_id', $accountId)->first();
        if (!$cashflow instanceof Cashflow) {
            $this->emptyLn();
            $this->error(__('cli.category.base.error.not_found_cashflow', ['bankAccountId' => $accountId]));
            return;
        }

        $this->emptyLn();
        $this->treeViewConsoleService->displayCashflowTrees($cashflow);
    }
}
