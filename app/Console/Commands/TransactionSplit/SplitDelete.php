<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\TransactionSplit;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;

class SplitDelete extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:split-delete {splitId : ' .
        '[:cli.transaction_split.base.param.transaction_split_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction_split.delete.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $splitId = (int)$this->argument('splitId');
        $splitEntry = TransactionSplit::find($splitId);
        if (!$splitEntry instanceof TransactionSplit) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $splitId]));
            return;
        }

        if (
            $this->confirmPrompt(
                __('cli.transaction_split.delete.confirm', ['id' => $splitId, 'comment' => $splitEntry->note])
            ) === false
        ) {
            return;
        }

        $splitEntry->delete();
        $this->info(__('cli.base.deleted', ['id' => $splitId]));
    }
}
