<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;

use function Laravel\Prompts\intro;

class AccountList extends FinCommand
{
    /**
     *
     * @inheritDoc
     */
    protected $signature = 'fin:account-list {--accountId= : [:cli.account.base.param.account_id:]}';

    /**
     *
     * @inheritDoc
     */
    protected $description = 'cli.account.list.description';

    /**
     * @inheritDoc
     */
    public function process(AccountListQuery $listQuery): void
    {
        $accountId = $this->option('accountId');
        $accounts = $listQuery->createList();
        if ($accountId) {
            $accounts->where('id', $accountId);
            intro(__('cli.account.list.details_title'));
        }

        $this->table(
            [
                __('cli.account.list.table.columns.id'),
                __('cli.account.list.table.columns.iban'),
                __('cli.account.list.table.columns.bic'),
                __('cli.account.list.table.columns.count_users')
            ],
            $accounts->get()->toArray()
        );
        if ($accountId) {
            $bankAccount = $accounts->first();
            if (!$bankAccount instanceof BankAccount) {
                return;
            }
            intro(__('cli.account.list.linked_user_title'));
            $this->table(
                [
                    __('cli.user.list.table.columns.id'),
                    __('cli.user.list.table.columns.mail'),
                ],
                $bankAccount->users->map(function ($user) {
                    return collect($user->toArray())
                            ->only('id', 'email')
                            ->all();
                })
            );
        }
    }
}
