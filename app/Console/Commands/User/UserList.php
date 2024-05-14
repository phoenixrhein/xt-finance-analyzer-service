<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\User;

use function Laravel\Prompts\intro;

class UserList extends FinCommand
{
    /**
     *
     * @inheritDoc
     */
    protected $signature = 'fin:user-list {--userId= : User id to show details}';

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
        $userId = $this->option('userId');
        $users = User::all(['id', 'email']);
        
        if ($userId) {
            $users = User::where('id', $userId)->select(['id', 'email'])->get();
            intro(__('cli.user.list.details_title'));
        }

 
        $this->table(
            [
                __('cli.user.list.table.columns.id'),
                __('cli.user.list.table.columns.mail')
            ],
            $users->toArray()
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
