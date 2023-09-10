<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Cashflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AccountAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:account-add {iban} {bic}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new bank account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $iban = $this->argument('iban');
        $bic = $this->argument('bic');
        $validator = Validator::make(
            [
                'iban' => $iban,
                'bic' => $bic
            ],
            BankAccount::$rules
        );
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return;
        }
        $newEntry = BankAccount::create(['iban' => $iban, 'bic' => $bic]);
        
        Cashflow::createWithCategories(['bank_account_id' => $newEntry->id]);
        $this->info("bamk account created with iban {$iban} and id {$newEntry->id}");
    }
}
