<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\IgnoreList as IgnoreListModel;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;

use function Laravel\Prompts\intro;

abstract class AbstractIgnoreList extends FinCommand
{
    /**
     *
     * @param integer $accountId
     * @return void
     */
    protected function displayIgnoreList(int $accountId)
    {
        $ignoreList = IgnoreListModel::where('bank_account_id', $accountId)
                        ->select(['id', 'type', 'value', 'comment']);
        if ($ignoreList->count() === 0) {
            $account = BankAccount::find($accountId);
            if (!$account instanceof BankAccount) {
                intro('BLA');
                return;
            }
        }
        $this->emptyLn();
        $this->table(
            ['Id', 'Type', 'Wert', 'Kommentar'],
            $ignoreList->get()->toArray()
        );
    }
}
