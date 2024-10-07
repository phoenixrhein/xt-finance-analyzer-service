<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;

use function Laravel\Prompts\text;

trait SelectAccountId
{
    use BaseView;

    /**
     *
     * @return AccountListQuery
     */
    abstract protected function getAccountlistQuery(): AccountListQuery;

    /**
     *
     * @param integer|null $rawAccountId
     * @return integer
     */
    protected function viewAccountId(int $rawAccountId = null): int
    {
        $accountId = $rawAccountId;
        do {
            $accountId = text(
                label: __('cli.ignore_list.upsert.edit_bank_account_id'),
                default: $accountId ?? ''
            );

            $valid = $this->viewValidatorError(
                [
                    'bank_account_id' => $accountId
                ],
                [
                    'bank_account_id' => 'required|numeric|exists:de\xovatec\financeAnalyzer\Models\BankAccount,id'
                ]
            );

            if (!$valid) {
                $this->emptyLn();
                $this->table(
                    [
                        __('cli.account.list.table.columns.id'),
                        __('cli.account.list.table.columns.iban'),
                        __('cli.account.list.table.columns.bic'),
                        __('cli.account.list.table.columns.count_users')
                    ],
                    $this->getAccountlistQuery()->createList()->get()->toArray()
                );
            }
        } while (!$valid);

        return (int)$accountId;
    }
}
