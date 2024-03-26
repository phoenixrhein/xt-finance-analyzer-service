<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Transaction;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Traits\TableConsolePagination;
use Symfony\Component\Console\Exception\InvalidOptionException;

class TransactionList extends Command
{
    use TableConsolePagination;

    /**
     *
     * @var array
     */
    private static $viewConfig = [
        'id' => 'Id',
        'booking_date' => 'Buchungstag',
        'exchange_date' => 'Valutadatum',
        'booking_type' => 'Buchungstext',
        'reason_for_payment' => 'Verwendungszweck',
        'creditor_id' => 'Glaeubiger ID',
        'mandate_ reference' => 'Mandatsreferenz',
        'customer_reference' => 'Kundenreferenz (End-to-End)',
        'collector_reference' => 'Sammlerreferenz',
        'debit_original_amount' => 'Lastschrift Ursprungsbetrag',
        'reimbursement_of_expenses_return_debit' => 'Auslagenersatz Ruecklastschrift',
        'beneficiary_payee' => 'Beguenstigter/Zahlungspflichtiger',
        'creditor_iban' => 'Kontonummer/IBAN',
        'creditor_bic' => 'BIC (SWIFT-Code)',
        'amount' => 'Betrag',
        'currency' => 'Waehrung',
        'note' => 'Anmerkung'
    ];

    /**
     *
     * @var array
     */
    public static $compactView = [
        'id' => 'Id',
        'booking_date' => 'Buchungstag',
        'booking_type' => 'Buchungstext',
        'reason_for_payment' => [
            'headline' => 'Verwendungszweck',
            'maxWidth' => 28
        ],
        'beneficiary_payee' => [
            'headline' => 'Beguenstigter/Zahlungspflichtiger',
            'maxWidth' => 24
        ],
        'creditor_iban' => 'Kontonummer/IBAN',
        'amount' => 'Betrag',
        'currency' => 'Waehrung',
        'note' => 'Anmerkung'
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:transaction-list {accountId} {--full} {--noLimit} {--range=} {--limit=25}';

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
    private function getHeadlines(array $config): array
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                return $item['headline'];
            }
            return $item;
        },
        $config);
    }

    /**
     *
     * @param array $config
     * @return array
     */
    private function getMaxWidth(array $config): array
    {
        return array_filter(array_map(function ($item) {
            if (is_array($item)) {
                return $item['maxWidth'];
            }
            return null;
        },
        $config));
    }

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
     *
     * @param string $date
     * @param boolean $start
     * @return string
     */
    private function parseDate(string $date, bool $start = true): string
    {
        $length = strlen($date);
        if (in_array($length, [0,4,6,8]) === false) {
            throw new InvalidOptionException("Invalid date {$date}");
        }

        if ($length == 6) {
            $date = Carbon::createFromFormat('mY', $date);
        } elseif ($length == 8) {
            $date = Carbon::createFromFormat('dmY', $date);
        } else {
            $date = Carbon::create($date);
        }

        if ($start && $length == 4) {
            $date = $date->startOfYear();
        } elseif ($start && $length == 6) {
            $date = $date->startOfMonth();
        } elseif ($start === false && $length == 4) {
            $date = $date->endOfYear();
        } elseif ($start === false && $length == 6) {
            $date = $date->endOfMonth();
        }
        return $date->format('Y-m-d');
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
            $range = explode('-', $this->option('range'));
            if (in_array(count($range), [1,2]) === false) {
                throw new InvalidOptionException('Invalid range: ' . $this->option('range'));
            }
            if (count($range) == 1) {
                $range[1] = $range[0];
            }

            $from = $this->parseDate($range[0]);
            $to = $this->parseDate($range[1], false);
        }

        $transactions = Transactions::where('bank_account_iban', $bankAccount->iban);

        if ($from !== null) {
            $transactions = $transactions->where('booking_date', '>=', $from)
                ->where('booking_date', '<=', $to);
        }

        $transactions = $transactions->select($this->getColumns($viewConfig))
                            ->orderBy('booking_date')
                            ->orderByDesc('id');

        $this->tableConsolePagination(
            $transactions->get(),
            $this->getHeadlines($viewConfig),
            $this->option('noLimit') ? null : $this->option('limit'),
            $this->getMaxWidth($viewConfig)
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
