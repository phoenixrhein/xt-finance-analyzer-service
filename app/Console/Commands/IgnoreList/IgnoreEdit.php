<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use Illuminate\Console\Command;

class IgnoreEdit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ignore-edit {accountId} {ignoreId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add or edit ignore list entry';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId =  $this->argument('accountId');
        BankAccount::findOrFail($accountId);
        $ignoreEntry = IgnoreList::find($this->argument('ignoreId'));
        if (empty($ignoreEntry)) {
            $ignoreEntry = new IgnoreList();
        }
        $ignoreEntry->bank_account_id = $accountId;
        $ignoreEntry->type = 'iban';
        $askIban = function () use ($ignoreEntry, &$askIban) {
            $iban = $this->ask('IBAN to ignore', $ignoreEntry->value);
            $ignoreEntry->value = $iban;
            if (strlen($iban) === 0) {
                $this->alert('Iban could not be empty');
                $askIban();
            }
        };
        $askIban();
        $comment = $this->ask('Comment', $ignoreEntry->comment);
        $ignoreEntry->comment = $comment;
        $ignoreEntry->save();
        $this->info("Entry edited.");
    }
}
