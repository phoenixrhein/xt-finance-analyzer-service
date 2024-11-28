<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\User;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class UserAddAccount extends FinCommand
{
    use BankAccountIdParameter;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-add-account {userId : [:cli.base.param.user_id:]}' .
                           ' {bankAccountId : [:cli.base.param.account_id:]}';

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

        if (!$this->getBankAccount((int)$this->argument('bankAccountId')) instanceof BankAccount) {
            return;
        }

        if ($user->bankAccounts()->where('bank_account.id', (int)$this->argument('bankAccountId'))->count() > 0) {
            $this->error(
                __('cli.user.addAccount.error.duplicate', ['accountId' => (int)$this->argument('bankAccountId')])
            );
            return;
        }

        $user->bankAccounts()->attach($this->argument('bankAccountId'));

        $this->info(
            __(
                'cli.user.addAccount.added',
                ['userId' => (int)$this->argument('userId'), 'accountId' => (int)$this->argument('bankAccountId')]
            )
        );
    }
}
