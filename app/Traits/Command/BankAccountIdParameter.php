<?php

namespace de\xovatec\financeAnalyzer\Traits\Command;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Traits\Command\View\BaseView;
use de\xovatec\financeAnalyzer\Traits\Command\View\SelectAccountId;

trait BankAccountIdParameter
{
    use BaseView;
    use SelectAccountId;

    /**
     *
     * @param integer $bankAccountId
     * @param bool $inputFallback
     * @return BankAccount|null
     */
    private function getBankAccount(int $bankAccountId, bool $inputFallback = false): ?BankAccount
    {
        $bankAccount = BankAccount::find($bankAccountId);
        if (!$bankAccount instanceof BankAccount) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $bankAccountId]));
            if (!$inputFallback) {
                return null;
            }
            $bankAccountId = $this->viewAccountId();
            $bankAccount = BankAccount::find($bankAccountId);
        }

        return $bankAccount;
    }
}
