<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;

use function Laravel\Prompts\text;

trait SelectAccountId
{
    use BaseView;
    use DisplayBankAccounts;

    /**
     *
     * @return AccountListQuery|null
     */
    protected function getAccountListQuery(): ?AccountListQuery
    {
        return null;
    }

    /**
     *
     * @param integer|null $rawAccountId
     * @param bool $cancellable
     * @return integer
     */
    protected function viewAccountId(?int $rawAccountId = null, bool $cancellable = false): ?int
    {
        $accountId = $rawAccountId;
        do {
            $this->displayBankAccounts();
            $accountId = text(
                label: __('cli.ignore_list.upsert.edit_bank_account_id'),
                default: $accountId ?? ''
            );

            if ($cancellable && (empty($accountId) || !is_numeric($accountId))) {
                return null;
            }

            $valid = $this->viewValidatorError(
                [
                    'bank_account_id' => $accountId
                ],
                [
                    'bank_account_id' => 'required|numeric|exists:de\xovatec\financeAnalyzer\Models\BankAccount,id'
                ]
            );
        } while (!$valid);

        return (int)$accountId;
    }
}
