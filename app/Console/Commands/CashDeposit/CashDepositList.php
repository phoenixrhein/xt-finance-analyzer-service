<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\CashDeposit;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\CashDeposit;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;

class CashDepositList extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cash-list  {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.cash_deposit.list.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $accountId = (int)$this->argument('accountId');

        $cashDepositList = CashDeposit::where('bank_account_id', $accountId)
        ->with('bankAccount')
        ->select(['id', 'bank_account_id', 'deposit_date', 'amount', 'currency', 'note'])
        ->get()
        ->map(function ($deposit) {
            return [
                'id' => $deposit->id,
                'iban' => $deposit->bankAccount->iban,
                'deposit_date' => \Carbon\Carbon::parse($deposit->deposit_date)->format('d.m.Y'),
                'amount' => $deposit->amount,
                'currency' => $deposit->currency,
                'note' => $deposit->note,
            ];
        });
        if ($cashDepositList->isEmpty()) {
            $account = BankAccount::find($accountId);
            if (!$account instanceof BankAccount) {
                $this->error(__('cli.base.error.not_found', ['id' => $accountId]));
                return;
            }
        }

        $this->emptyLn();
        $this->table(
            [
                __('cli.cash_deposit.list.table_header.id'),
                __('cli.cash_deposit.list.table_header.iban'),
                __('cli.cash_deposit.list.table_header.cash_deposit_date'),
                __('cli.cash_deposit.list.table_header.amount'),
                __('cli.cash_deposit.list.table_header.currency'),
                __('cli.cash_deposit.list.table_header.note')
            ],
            $cashDepositList->toArray()
        );
    }
}
