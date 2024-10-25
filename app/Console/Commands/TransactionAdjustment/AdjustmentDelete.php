<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\TransactionAdjustment;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\TransactionAdjustment;

class AdjustmentDelete extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:adjust-delete {adjustId : [:cli.transaction_adjustment.base.param.transaction_adjustment_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction_adjustment.delete.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $adjustId = (int)$this->argument('adjustId');
        $adjustEntry = TransactionAdjustment::find($adjustId);
        if (!$adjustEntry instanceof TransactionAdjustment) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $adjustId]));
            return;
        }

        if (
            $this->confirmPrompt(
                __('cli.transaction_adjustment.delete.confirm', ['id' => $adjustId, 'comment' => $adjustEntry->note])
            ) === false
        ) {
            return;
        }

        $adjustEntry->delete();
        $this->info(__('cli.base.deleted', ['id' => $adjustId]));
    }
}
