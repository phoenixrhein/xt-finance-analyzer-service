<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\CashDeposit;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\CashDeposit;

class CashDepositDelete extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cash-delete {cashDepositId : [:cli.cash_deposit.base.param.cash_deposit_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.cash_deposit.delete.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $cashDepositId = (int)$this->argument('cashDepositId');
        $cashDepositEntry = CashDeposit::find($cashDepositId);
        if (!$cashDepositEntry instanceof CashDeposit) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $cashDepositId]));
            return;
        }

        if (
            $this->confirmPrompt(
                __('cli.cash_deposit.delete.confirm', ['id' => $cashDepositId, 'comment' => $cashDepositEntry->note])
            ) === false
        ) {
            return;
        }

        $cashDepositEntry->delete();
        $this->info(__('cli.base.deleted', ['id' => $cashDepositId]));
    }
}
