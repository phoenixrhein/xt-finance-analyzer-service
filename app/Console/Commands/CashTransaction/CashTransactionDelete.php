<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\CashTransaction;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\CashTransaction;

class CashTransactionDelete extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cash-delete {cashTransactionId : '.
        '[:cli.cash_transaction.base.param.cash_transaction_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.cash_transaction.delete.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $cashTransactionId = (int)$this->argument('cashTransactionId');
        $cashTransactionEntry = CashTransaction::find($cashTransactionId);
        if (!$cashTransactionEntry instanceof CashTransaction) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $cashTransactionId]));
            return;
        }

        if (
            $this->confirmPrompt(
                __(
                    'cli.cash_transaction.delete.confirm',
                    ['id' => $cashTransactionId, 'comment' => $cashTransactionEntry->note]
                )
            ) === false
        ) {
            return;
        }

        $cashTransactionEntry->delete();
        $this->info(__('cli.base.deleted', ['id' => $cashTransactionId]));
    }
}
