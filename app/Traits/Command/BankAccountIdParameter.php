<?php

namespace de\xovatec\financeAnalyzer\Traits\Command;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Traits\Command\View\BaseView;

trait BankAccountIdParameter
{
    use BaseView;

    /**
     *
     * @param integer $bankAccountId
     * @return BankAccount|null
     */
    private function getBankAccount(int $bankAccountId): ?BankAccount
    {
        $bankAccount = BankAccount::find($bankAccountId);
        if (!$bankAccount instanceof BankAccount) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $bankAccountId]));
            return null;
        }

        return $bankAccount;
    }
}
