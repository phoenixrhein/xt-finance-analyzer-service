<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\CashTransaction;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\CashTransaction;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class CashTransactionList extends FinCommand
{
    use BankAccountIdParameter;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cash-list {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.cash_transaction.list.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $accountId = (int)$this->argument('accountId');

        $cashTransactionList = CashTransaction::where('bank_account_id', $accountId)
        ->with('bankAccount')
        ->select(['id', 'bank_account_id', 'cash_booking_date', 'amount', 'currency', 'note'])
        ->get()
        ->map(function ($cashTransaction) {
            return [
                'id' => $cashTransaction->id,
                'iban' => $cashTransaction->bankAccount->iban,
                'cash_booking_date' => \Carbon\Carbon::parse($cashTransaction->cash_booking_date)->format('d.m.Y'),
                'amount' => $cashTransaction->amount,
                'currency' => $cashTransaction->currency,
                'note' => $cashTransaction->note,
            ];
        });
        if ($cashTransactionList->isEmpty()) {
            $bankAccount = $this->getBankAccount($accountId);
            if (!$bankAccount instanceof BankAccount) {
                return;
            }
        }

        $this->emptyLn();
        $this->table(
            [
                __('cli.cash_transaction.list.table_header.id'),
                __('cli.cash_transaction.list.table_header.iban'),
                __('cli.cash_transaction.list.table_header.cash_cash_booking_date'),
                __('cli.cash_transaction.list.table_header.amount'),
                __('cli.cash_transaction.list.table_header.currency'),
                __('cli.cash_transaction.list.table_header.note')
            ],
            $cashTransactionList->toArray()
        );
    }
}
