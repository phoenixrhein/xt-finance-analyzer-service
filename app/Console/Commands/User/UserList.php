<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\intro;

use de\xovatec\financeAnalyzer\Models\User;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;

class UserList extends FinCommand
{
    /**
     *
     * @inheritDoc
     */
    protected $signature = 'fin:user-list {userId : [:cli.user.list.param.user_id:]}';

    /**
     *
     * @inheritDoc
     */
    protected $description = 'cli.user.list.description';

    /**
     *
     * @inheritDoc
     */
    public function process(): void
    {
        $userId = $this->argument('userId');
        $users = User::leftJoin('bank_account_user', 'user.id', '=', 'bank_account_user.user_id')
            ->select('user.id', 'user.email', DB::raw('COUNT(bank_account_user.bank_account_id) as accounts_count'))
            ->groupBy('user.id', 'user.email');

        if ($userId) {
            $users->where('id', $userId);
            intro(__('cli.user.list.details_title'));
        }

        $this->table(
            [
                __('cli.user.list.table.columns.id'),
                __('cli.user.list.table.columns.mail'),
                __('cli.user.list.table.columns.count_accounts')
            ],
            $users->get()->toArray()
        );

        if ($userId) {
            intro(__('cli.user.list.linked_account_title'));
            $this->table(
                [
                    __('cli.account.list.table.columns.id'),
                    __('cli.account.list.table.columns.iban'),
                    __('cli.account.list.table.columns.bic')
                ],
                $users->first()->bankAccounts->map(function ($bankAccount) {
                    return collect($bankAccount->toArray())
                            ->only('id', 'iban', 'bic')
                            ->all();
                })
            );
        }
    }
}
