<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\TransactionSplit;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use Symfony\Component\Console\Helper\TableSeparator;
use de\xovatec\financeAnalyzer\Enums\TransactionType;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use de\xovatec\financeAnalyzer\Traits\Command\DateRangeParameter;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Enums\TransactionCheckCode as CheckCode;

class CashDetector extends FinCommand
{
    use SimpleInput;
    use DateRangeParameter;
    use BankAccountIdParameter;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cash-detector {accountId : [:cli.base.param.account_id:]}' .
        ' {--range= : [:cli.param.date_range.description:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction_split.cash_detector.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $range = [];
        if (strlen($this->option('range')) > 0) {
            $range = $this->prepareRangeParam($this->option('range'));
            if ($range === null) {
                return;
            }
        }

        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'));
        if (!$bankAccount instanceof BankAccount) {
            return;
        }

        $transactions = $this->getTransactions($bankAccount, $range);

        if ($transactions === null) {
            return;
        }

        $this->emptyLn();
        foreach ($transactions as $transaction) {
            $this->viewTransaction($transaction);
            $amount = $this->viewAmountInput($transaction);

            if ($amount != '') {
                TransactionSplit::create([
                    'transaction_id' => $transaction['id'],
                    'amount' => $amount,
                    'note' => __('cli.transaction_split.cash_detector.note')
                ]);
            }

            Transactions::where('id', $transaction['id'])
                ->update([
                    'checks_code' => DB::raw('checks_code | ' . CheckCode::DETECTED_CASH_PAYMENT->value)
                ]);
        }
    }

    /**
     *
     * @param Transactions $transaction
     * @return float
     */
    private function viewAmountInput(Transactions $transaction): float
    {
        $determinedAmount = Str::replace(',', '.', $this->extractCashAmount($transaction['reason_for_payment']));
        do {
            $valid = true;
            $amount = $this->viewInput(
                __('cli.transaction_split.cash_detector.amount'),
                'nullable|numeric|regex:/^\d+(\.\d{2})?$/',
                $determinedAmount,
                self::VALUE_TYPE_DECIMAL,
                __('cli.transaction_split.cash_detector.amount_note')
            );

            if (
                $amount != ''
                && $amount !== $determinedAmount
                && $this->confirmPrompt(__('cli.transaction_split.cash_detector.confirm')) === false
            ) {
                $valid = false;
            }
        } while (!$valid);

        return $amount;
    }

    /**
     *
     * @param Transactions $transaction
     * @return void
     */
    private function viewTransaction(Transactions $transaction): void
    {
        $this->table(
            [],
            [
                [
                    '<info>' . __('cli.transaction.base.table.header.reason_for_payment') . '</info>',
                    $this->addInfoTagToAmount($transaction['reason_for_payment'])
                ],
                new TableSeparator(),
                [
                    '<info>' . __('cli.base.payee') . '</info>',
                    $transaction['beneficiary_payee']
                ]
            ]
        );
    }

    /**
     *
     * @param BankAccount $bankAccount
     * @param array $range
     * @return Collection|null
     */
    private function getTransactions(BankAccount $bankAccount, array $range): ?Collection
    {
        $transactions = Transactions::where('transactions.bank_account_iban', $bankAccount->iban)
        ->where('transaction_type', TransactionType::CARD_PAYMENT_WITH_CASH_PAYMENT->value)
        ->whereRaw('checks_code & ' . CheckCode::DETECTED_CASH_PAYMENT->value . ' = ' . CheckCode::NONE->value);

        if (count($range)) {
            $transactions->where('transaction_date', '>=', $range['from'])
            ->where('transaction_date', '<=', $range['to']);
        }

        $transactions = $transactions->get([
            'id',
            'reason_for_payment',
            'beneficiary_payee'
        ]);

        if ($transactions->isEmpty()) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_rows_found'));
            return null;
        }

        return $transactions;
    }

    /**
     *
     * @param string $string
     * @return string|null
     */
    private function extractCashAmount(string $string): ?string
    {
        preg_match('/Bargeldausz\.\s([0-9,]+)\sEUR/', $string, $matches);
        return $matches[1] ?? null;
    }

    /**
     *
     * @param string $string
     * @return string
     */
    private function addInfoTagToAmount(string $string): string
    {
        return preg_replace('/(Bargeldausz\.\s)([0-9,]+)(\sEUR)/', '$1<info>$2</info>$3', $string);
    }
}
