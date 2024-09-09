<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Transaction;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Traits\TableConsolePagination;
use Illuminate\Database\Eloquent\Collection;

use function Laravel\Prompts\text;

class CommentEdit extends FinCommand
{
    use TableConsolePagination;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:comment-edit {transactionId : [:cli.transaction.base.param.transaction_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction.comment.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $transactionId = $this->argument('transactionId');
        $transaction = Transactions::where('id', $this->argument('transactionId'))
                        ->select(array_keys(TransactionList::$compactView))
                        ->first();
        if (!$transaction instanceof Transactions) {
            $this->emptyLn();
            $this->error(
                __('cli.transaction.base.error.not_found_transaction_id', ['transactionId' => $transactionId])
            );
            return;
        }

        $this->tableConsolePagination(
            new Collection([$transaction]),
            array_map(
                function ($item) {
                    if (is_array($item)) {
                        return $item['headline'];
                    }
                    return $item;
                },
                TransactionList::$compactView
            ),
            null,
            array_filter(array_map(function ($item) {
                if (is_array($item)) {
                    return $item['maxWidth'];
                }
                return null;
            },
            TransactionList::$compactView))
        );

        $transaction->note = text(
            label: __('cli.transaction.comment.input_note'),
            default: $transaction->note
        );

        $transaction->save();
        $this->info(__('cli.transaction.comment.edited'));
    }
}
