<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\IgnoreList as IgnoreListModel;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

abstract class AbstractIgnoreList extends FinCommand
{
    use BankAccountIdParameter;
    
    /**
     *
     * @param integer $accountId
     * @return void
     */
    protected function displayIgnoreList(int $accountId): void
    {
        $ignoreList = IgnoreListModel::where('bank_account_id', $accountId)
                        ->select(['id', 'type', 'value', 'comment']);
        if ($ignoreList->count() === 0) {
            $bankAccount = $this->getBankAccount($accountId);
            if (!$bankAccount instanceof BankAccount) {
                return;
            }
        }
        $this->emptyLn();
        $this->table(
            [
                __('cli.ignore_list.list.table_header.id'),
                __('cli.ignore_list.list.table_header.type'),
                __('cli.ignore_list.list.table_header.value'),
                __('cli.ignore_list.list.table_header.comment')
            ],
            $ignoreList->get()->toArray()
        );
    }
}
