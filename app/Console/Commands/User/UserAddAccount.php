<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\User;

class UserAddAccount extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-add-account {userId : [:cli.base.param.user_id:]}' .
                           ' {bankAccountid : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.user.addAccount.description';

    /**
     * Execute the console command.
     */
    protected function process(): void
    {
        $user = User::find((int)$this->argument('userId'));
        $this->emptyLn();

        if (!$user instanceof User) {
            $this->error(__('cli.base.error.not_found_user', ['userId' => (int)$this->argument('userId')]));
            return;
        }

        $account = BankAccount::find((int)$this->argument('bankAccountid'));

        if (!$account instanceof BankAccount) {
            $this->error(
                __('cli.base.error.not_found_account', ['accountId' => (int)$this->argument('bankAccountid')])
            );
            return;
        }

        if ($user->bankAccounts()->where('bank_account.id', (int)$this->argument('bankAccountid'))->count() > 0) {
            $this->error(
                __('cli.user.addAccount.error.duplicate', ['accountId' => (int)$this->argument('bankAccountid')])
            );
            return;
        }

        $user->bankAccounts()->attach($this->argument('bankAccountid'));

        $this->info(
            __(
                'cli.user.addAccount.added',
                ['userId' => (int)$this->argument('userId'), 'accountId' => (int)$this->argument('bankAccountid')]
            )
        );
    }
}
