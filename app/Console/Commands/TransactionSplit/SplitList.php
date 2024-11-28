<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\TransactionSplit;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class SplitList extends FinCommand
{
    use BankAccountIdParameter;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:split-list  {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction_split.list.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'));
        if (!$bankAccount instanceof BankAccount) {
            return;
        }

        $transactionSplits = TransactionSplit::query()
            ->join('transactions', 'transaction_split.transaction_id', '=', 'transactions.id')
            ->where('transactions.bank_account_iban', $bankAccount->iban)
            ->get([
                'transaction_split.id',
                'transaction_split.note',
                'transaction_split.amount',
                'transactions.transaction_date',
                'transactions.amount as total_amount',
                'transactions.reason_for_payment',
                'transactions.beneficiary_payee',
            ])
            ->map(function ($splitEntry) {
                $splitEntry->transaction_date = Carbon::parse($splitEntry->transaction_date)->format('d.m.Y');
                return $splitEntry;
            });

        if ($transactionSplits->isEmpty()) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_rows_found'));
            return;
        }

        $this->emptyLn();
        $this->table(
            [
                __('cli.transaction_split.list.table_header.id'),
                __('cli.transaction_split.list.table_header.note'),
                __('cli.transaction_split.list.table_header.split_amount'),
                __('cli.transaction.base.table.header.transaction_date'),
                __('cli.transaction_split.list.table_header.total_amount'),
                __('cli.transaction.base.table.header.reason_for_payment'),
                __('cli.transaction.base.table.header.beneficiary_payee')
            ],
            $transactionSplits->toArray()
        );
    }
}
