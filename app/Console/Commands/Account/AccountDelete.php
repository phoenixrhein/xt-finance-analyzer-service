<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use Illuminate\Console\Command;

class AccountDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:account-delete {accountId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete bank account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Do you want to delete?') === false) {
            return;
        }
        $accountId = $this->argument('accountId');
        BankAccount::destroy($accountId);
        $this->info(("Deleted bank account with id '{$accountId}'"));
    }
}
