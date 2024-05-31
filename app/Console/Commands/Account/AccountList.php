<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\intro;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;

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
    protected function process(): void
    {
        $accountId = $this->option('accountId');
        $accounts = BankAccount::leftJoin('bank_account_user', 'bank_account.id', '=', 'bank_account_user.bank_account_id')
        ->select('bank_account.id', 'bank_account.iban', 'bank_account.bic', DB::raw('COUNT(bank_account_user.user_id) as accounts_count'))
        ->groupBy('bank_account.id', 'bank_account.iban', 'bank_account.bic');
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
            intro(__('cli.account.list.linked_user_title'));
            $this->table(
                [
                    __('cli.user.list.table.columns.id'),
                    __('cli.user.list.table.columns.mail'),
                ],
                $accounts->first()->users->map(function ($user) {
                    return collect($user->toArray())
                            ->only('id', 'email')
                            ->all();
                })
            );
        }
    }
}
