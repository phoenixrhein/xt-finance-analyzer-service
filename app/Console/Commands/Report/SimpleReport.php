<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Report;

use Exception;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Enums\ReportType;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Exception\InvalidOptionException;

class SimpleReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:simple-report {accountId} {type} {--timespan=3} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     *
     * @param string $to
     * @param integer $span
     * @param ReportType $type
     * @return array
     */
    private function calculateTimeRanges(string $to, int $span, ReportType $type): array
    {
        $length = strlen($to);
        if (in_array($length, [4, 6, 8]) === false) {
            throw new InvalidOptionException("Invalid date {$to}");
        }

        if ($length == 4) {
            $to = Carbon::createFromFormat('Y', $to)->endOfYear();
        } elseif ($length == 6) {
            $to = Carbon::createFromFormat('mY', $to)->endOfMonth();
        } elseif ($length == 8) {
            $to = Carbon::createFromFormat('dmY', $to);
        }

        $ranges = [];

        for ($i = 1; $i <= $span; $i++) {
            $from = clone $to;
            if ($type == ReportType::year) {
                $from = $from->startOfYear();
            } elseif ($type == ReportType::month) {
                $from = $from->startOfMonth();
            }

            $ranges[] = [$from->format('Y-m-d'), $to->format('Y-m-d')];

            if ($length == 8) {
                if ($type == ReportType::year) {
                    $to = $to->subYear();
                } elseif ($type == ReportType::month) {
                    $to = $to->subMonth();
                }
            } else {
                $to = $from->subDay();
            }
        }

        return $ranges;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Assert::numeric($this->argument('accountId'));
            $bankAccount = BankAccount::findOrFail($this->argument('accountId'));
            Assert::inArray(strtolower($this->argument('type')), ['y', 'm', 'year', 'month']);
            if (strlen($this->option('to')) > 0) {
                Assert::numeric($this->option('to'));
            }
            Assert::numeric($this->option('timespan'));
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $type = substr(strtolower($this->argument('type')), 0, 1) === 'y' ? ReportType::year : ReportType::month;
        $to = $this->option('to') ?? Carbon::now()->format('dmY');
        $ranges = $this->calculateTimeRanges($to, $this->option('timespan'), $type);

        if (empty($ranges)) {
            throw new RuntimeException('No ranges found');
        }

        $maxTo = Carbon::parse($ranges[0][1]);

        $countOfLast = Transactions::whereBetween(
            'booking_date',
            [
                $maxTo->clone()->subDays(5)->format('Y-m-d'),
                $maxTo->format('Y-m-d')
            ]
        )->where('bank_account_iban', $bankAccount->iban)->count();

        if ($countOfLast === 0 && $maxTo <= Carbon::now()) {
            throw new RuntimeException('No current data available');
        }

        $ignoreIbans = IgnoreList::where('bank_account_id', $this->argument('accountId'))->select('value')->get();

        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $totalSaldo = 0;
        foreach ($ranges as $range) {
            $transactions = Transactions::whereBetween(
                'booking_date',
                [
                    Carbon::parse($range[0])->format('Y-m-d'),
                    Carbon::parse($range[1])->format('Y-m-d')
                ]
            )->where('bank_account_iban', $bankAccount->iban);
            $transactions = $transactions->whereNotIn('creditor_iban', $ignoreIbans->toArray());

            $debit = 0;
            $credit = 0;
            foreach (Arr::pluck($transactions->get()->toArray(), 'amount') as $amount) {
                if ($amount > 0) {
                    $debit = round($debit + $amount, 2);
                } else {
                    $credit = round($credit + $amount, 2);
                }
            }
            $saldo = round($credit + $debit, 2);
            if (strlen($to) === 8) {
                if ($type === ReportType::year) {
                    $name = Carbon::parse($range[1])->format('d. F Y');
                } else {
                    $name = Carbon::parse($range[1])->format('d. F');
                }
            } elseif ($type === ReportType::year) {
                $name = Carbon::parse($range[1])->format('Y');
            } else {
                $name = Carbon::parse($range[1])->format('F');
            }
            $rows[] = [
                '<info>' . $name . '</info>',
                $debit,
                $credit,
                $saldo,
                Carbon::parse($range[0])->format('d.m.Y') . ' - ' . Carbon::parse($range[1])->format('d.m.Y')
            ];

            $totalDebit = round($totalDebit + $debit, 2);
            $totalCredit = round($totalCredit + $credit, 2);
            $totalSaldo = round($totalSaldo + $saldo, 2);
        }

        $rows[] = [
            '<info>Total</info>',
            $totalDebit,
            $totalCredit,
            $totalSaldo,
            ''
        ];

        $this->table(['', 'Einnahmen', 'Ausgaben', 'Saldo', 'Range'], $rows);
    }
}
