<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use Illuminate\Console\Command;

class AccountList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:account-list {--accountId=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a list of all bank accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->option('accountId');
        $accounts = BankAccount::all(['id', 'iban', 'bic']);
        if ($accountId) {
            $accounts = BankAccount::where('id', $accountId)
                    ->select(['id', 'iban', 'bic'])->get();
        }
        $this->table(
            ['Id', 'IBAN', 'BIC'],
            $accounts->toArray()
        );
        if ($accountId) {
            $this->table(
                ['Id', 'Email'],
                $accounts->first()->users->map(function($user) {
                    return collect($user->toArray())
                            ->only('id', 'email')
                            ->all();
                })
            );
        }
    }
}
