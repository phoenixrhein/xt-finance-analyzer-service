<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Transaction;

use de\xovatec\financeAnalyzer\Helpers\DateRangeHelper;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Traits\Command\DateRangeParameter;
use de\xovatec\financeAnalyzer\Traits\Command\View\TableConsolePagination;

class TransactionList extends Command
{
    use TableConsolePagination;
    use DateRangeParameter;

    /**
     *
     * @var array
     */
    private static $viewConfig = [
        'id' => null,
        'transaction_date' => null,
        'exchange_date' => null,
        'transaction_type' => null,
        'reason_for_payment' => null,
        'creditor_id' => null,
        'mandate_ reference' => null,
        'customer_reference' => null,
        'collector_reference' => null,
        'debit_original_amount' => null,
        'reimbursement_of_expenses_return_debit' => null,
        'beneficiary_payee' => null,
        'creditor_iban' => null,
        'creditor_bic' => null,
        'amount' => null,
        'currency' => null,
        'note' => null
    ];

    /**
     *
     * @var array
     */
    public static $compactView = [
        'id' => [
            'width' => 7
        ],
        'transaction_date' => [
            'width' => 10
        ],
        'transaction_type' => [
            'width' => 35
        ],
        'reason_for_payment' => [
            'width' => '50%'
        ],
        'beneficiary_payee' => [
            'width' => '50%'
        ],
        'creditor_iban' => [
            'width' => 27
        ],
        'amount' => [
            'width' => 8
        ],
        'currency' => [
            'width' => 7
        ],
        'note' => [
            'width' => 9
        ]
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:transaction-list {accountId} {--full} {--noLimit}' .
        ' {--range= : [:cli.param.date_range.description:]} {--limit=25}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'transaction list ';

    /**
     *
     * @param array $config
     * @return array
     */
    private function getColumns(array $config): array
    {
        return array_keys($config);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bankAccount = BankAccount::findOrFail($this->argument('accountId'));
        $viewConfig = static::$compactView;
        if ($this->option('full')) {
            $viewConfig = static::$viewConfig;
        }

        $from = null;
        $to = null;
        if (strlen($this->option('range')) > 0) {
            $range = $this->prepareRangeParam($this->option('range'));
            if ($range === null) {
                return null;
            }

            $from = $range[DateRangeHelper::FROM];
            $to = $range[DateRangeHelper::TO];
        }

        $transactions = Transactions::where('bank_account_iban', $bankAccount->iban);

        if ($from !== null) {
            $transactions = $transactions->where('transaction_date', '>=', $from)
                ->where('transaction_date', '<=', $to);
        }

        $transactions = $transactions->select($this->getColumns($viewConfig))
                            ->orderBy('transaction_date')
                            ->orderByDesc('id');

        $this->tableConsolePagination(
            $transactions->get(),
            $viewConfig,
            $this->option('noLimit') ? null : $this->option('limit'),
            'cli.transaction.base.table.header.'
        );

        $sum = 0;
        $ignoreIbans = IgnoreList::where('bank_account_id', $this->argument('accountId'))->select('value')->get();
        $transactions = $transactions->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        foreach (Arr::pluck($transactions->get()->toArray(), 'amount') as $amount) {
            $sum = round($sum + $amount, 2);
        }
        $totalAmount = number_format($sum, 2, ',', '');
        $this->info('Anzahl: ' . count($transactions->get()->toArray()) . ' / Betrag: ' . $totalAmount);
    }
}
