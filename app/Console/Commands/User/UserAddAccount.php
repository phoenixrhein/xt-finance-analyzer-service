<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\User;

use de\xovatec\financeAnalyzer\Models\User;
use Illuminate\Console\Command;

class UserAddAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:user-add-account {userId} {bankAccountid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign an user to a account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::find($this->argument('userId'));

        $user->bankAccounts()->attach($this->argument('bankAccountid'));
        $this->info(
            'Assigned user with id ' . $this->argument('userId') .
            ' to bank account with id ' . $this->argument('bankAccountid')
        );
    }
}
