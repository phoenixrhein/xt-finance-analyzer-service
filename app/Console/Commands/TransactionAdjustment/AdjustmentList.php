<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\TransactionAdjustment;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\TransactionAdjustment;

class AdjustmentList extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:adjust-list  {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction_adjustment.list.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $accountId = (int)$this->argument('accountId');

        $bankAccount = BankAccount::find($accountId);
        if (!$bankAccount instanceof BankAccount) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $accountId]));
            return;
        }

        $transactionAdjustments = TransactionAdjustment::query()
            ->join('transactions', 'transaction_adjustment.transaction_id', '=', 'transactions.id')
            ->where('transactions.bank_account_iban', $bankAccount->iban)
            ->get([
                'transaction_adjustment.id',
                'transaction_adjustment.note',
                'transaction_adjustment.transaction_date',
                'transactions.transaction_date as transactions_transaction_date',
                'transactions.reason_for_payment',
                'transactions.beneficiary_payee',
            ])
            ->map(function ($adjustment) {
                $adjustment->transaction_date = Carbon::parse($adjustment->transaction_date)->format('d.m.Y');
                $adjustment->transactions_transaction_date = Carbon::parse($adjustment->transactions_transaction_date)->format('d.m.Y');
                return $adjustment;
            });

        if ($transactionAdjustments->isEmpty()) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_rows_found'));
            return;
        }

        $this->emptyLn();
        $this->table(
            [
                __('cli.transaction_adjustment.list.table_header.id'),
                __('cli.transaction_adjustment.list.table_header.note'),
                __('cli.transaction_adjustment.list.table_header.new_date'),
                __('cli.transaction.base.table.header.transaction_date'),
                __('cli.transaction.base.table.header.reason_for_payment'),
                __('cli.transaction.base.table.header.beneficiary_payee')
            ],
            $transactionAdjustments->toArray()
        );
    }
}
