<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AccountEdit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:account-edit {accountId} {--iban=} {--bic=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Edit bank account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->argument('accountId');
        $account = BankAccount::findOrFail($accountId);
        if (strlen(implode('', array_values($this->options()))) === 0) {
            $this->info("No option for update");
            exit();
        }
        $iban = $this->option('iban') ?? $account->iban;
        $bic = $this->option('bic') ?? $account->bic;
        $validator = Validator::make(
            ['iban' => $iban, 'bic' => $bic],
            BankAccount::$rules
        );
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return;
        }
        $account->iban = $iban;
        $account->bic = $bic;
        $account->save();
        $this->info("Updated account with id '{$accountId}'");
    }
}
