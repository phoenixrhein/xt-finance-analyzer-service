<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Models\IgnoreList as IgnoreListModel;

class IgnoreList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ignore-list {accountId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List of ignore data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->argument('accountId');
        if (!is_numeric($accountId)) {
            $this->error('bank account expected');
            exit();
        }
        $accounts = IgnoreListModel::where('bank_account_id', $accountId)
                ->select(['id', 'type', 'value', 'comment'])->get();
        $this->table(
            ['Id', 'Type', 'Wert', 'Kommentar'],
            $accounts->toArray()
        );
    }
}
