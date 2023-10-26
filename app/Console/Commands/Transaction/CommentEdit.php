<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Transaction;

use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Traits\TableConsolePagination;

class CommentEdit extends Command
{
    use TableConsolePagination;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:comment-edit {transactionId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Edit a comment of transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transaction = Transactions::findOrFail($this->argument('transactionId'));
        $transactions = Transactions::where('id', $this->argument('transactionId'))
                            ->select(array_keys(TransactionList::$compactView))
                            ->get();
        $this->tableConsolePagination(
            $transactions,
            array_map(function($item) {
                    if (is_array($item)) {
                        return $item['headline'];
                    }
                    return $item;
                },
            TransactionList::$compactView),
            null,
            array_filter(array_map(function($item) {
                if (is_array($item)) {
                    return $item['maxWidth'];
                }
                return null;
            },
            TransactionList::$compactView))
        );
        if (!empty($transaction->note)) {
            $this->info('Current comment: ' . $transaction->note);
        }
        $note = $this->ask('Comment');

        if (strlen($transaction->note) > 0 && strlen($note) === 0) {
            if ($this->confirm('Do you want to delete the current comment?') === false) {
                return;
            }
        } elseif (strlen($transaction->note) !== strlen($note)
            && $this->confirm('Do you want to overwrite the current comment?') === false
        ) {
            return;
        }

        $transaction->note = $note ?? '';
        $transaction->save();
        $this->info('The comment was changed.');
    }
}
