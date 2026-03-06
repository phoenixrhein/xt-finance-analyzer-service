<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;

trait DisplayBankAccounts
{
    /**
     *
     * @return AccountListQuery|null
     */
    abstract protected function getAccountListQuery(): ?AccountListQuery;

    /**
     *
     * @return void
     */
    protected function displayBankAccounts(): void
    {
        if (!$this->getAccountListQuery() instanceof AccountListQuery) {
            return;
        }

        $accounts = $this->getAccountListQuery()->createList();

        $this->table(
            [
                __('cli.account.list.table.columns.id'),
                __('cli.account.list.table.columns.iban'),
                __('cli.account.list.table.columns.bic'),
                __('cli.account.list.table.columns.count_users')
            ],
            $accounts->get()->toArray()
        );
    }
}
