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
     * @param integer|null $bankAccountId
     * @param bool $inputFallback
     * @param bool $cancellable
     * @return BankAccount|null
     */
    private function getBankAccount(
        ?int $bankAccountId = null,
        bool $inputFallback = false,
        bool $cancellable = false
    ): ?BankAccount {
        $bankAccount = $bankAccountId !== null ? BankAccount::find($bankAccountId) : null;
        if (!$bankAccount instanceof BankAccount) {
            $this->emptyLn();
            if ($bankAccountId !== null) {
                $this->error(__('cli.base.error.not_found', ['id' => $bankAccountId]));
            }
            if (!$inputFallback) {
                return null;
            }
            $bankAccountId = $this->viewAccountId(null, $cancellable);
            if ($bankAccountId === null) {
                return null;
            }
            $bankAccount = BankAccount::find($bankAccountId);
        }

        return $bankAccount;
    }
}
