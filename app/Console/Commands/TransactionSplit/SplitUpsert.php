<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\TransactionSplit;

use Illuminate\Database\Eloquent\Collection;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use de\xovatec\financeAnalyzer\Traits\Command\View\SelectAccountId;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;
use de\xovatec\financeAnalyzer\Traits\Command\View\FindAndSelectTransaction;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class SplitUpsert extends FinCommand
{
    use SelectAccountId;
    use SimpleInput;
    use FindAndSelectTransaction;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:split-upsert {splitId? : ' .
        '[:cli.transaction_split.base.param.transaction_split_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction_split.upsert.description';

    /**
     *
     * @param AccountListQuery $accountlistQuery
     */
    public function __construct(private AccountListQuery $accountlistQuery)
    {
        parent::__construct();
    }

    /**
     *
     * @return AccountListQuery
     */
    protected function getAccountlistQuery(): AccountListQuery
    {
        return $this->accountlistQuery;
    }

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $valid = true;
        do {
            $splitId = (int)$this->argument('splitId');
            $isAdd = $splitId <= 0;

            if ($valid !== false) {
                $splitEntry = $this->initializeInput($splitId, $isAdd);
                if ($splitEntry === null) {
                    return;
                }
                $bankAccount = $this->inputBankAccount($splitEntry, $isAdd);
            }

            $valid = true;
            $transaction = $this->inputTransaction($splitEntry, $bankAccount, $isAdd);
            $totalSplittedAmount = TransactionSplit::where('transaction_id', $transaction->id)->sum('amount');

            $rest = number_format(
                (abs($transaction->amount) - ($isAdd ?
                    $totalSplittedAmount :
                    ($totalSplittedAmount - $splitEntry->amount
                ))),
                2,
                ',',
                ''
            );
            $this->info(__(
                'cli.transaction_split.upsert.available_remaining_amount',
                ['rest' => $rest . ' ' . $transaction->currency]
            ));

            $splitEntry->amount = $this->inputAmount($transaction, $totalSplittedAmount, $splitEntry->amount, $isAdd);
            if ($splitEntry->amount === null) {
                return;
            }

            $splitEntry->note  = $this->viewInput(
                __('cli.transaction_split.upsert.note'),
                ['required'],
                $splitEntry->note
            );

            if (!$this->confirmPrompt(__('cli.base.confirm_save'))) {
                $valid = false;
            }
        } while (!$valid);

        $labelKey = 'cli.base.created';
        if (!$isAdd) {
            $labelKey = 'cli.base.edited';
        }

        $splitEntry->save();
        $this->info(__($labelKey, ['id' => $splitEntry->id]));
    }

    /**
     *
     * @param integer $splitId
     * @param boolean $isAdd
     * @return TransactionSplit|null
     */
    private function initializeInput(int $splitId, bool $isAdd): ?TransactionSplit
    {
        if ($isAdd) {
            $splitEntry = new TransactionSplit();
            warning(__('cli.base.upsert_hint_add'));
        } else {
            $splitEntry = TransactionSplit::find($splitId);
            if (!$splitEntry instanceof TransactionSplit) {
                $this->emptyLn();
                $this->error(__('cli.base.error.not_found', ['id' => $splitId]));
                return null;
            }
        }

        return $splitEntry;
    }

        /**
         *
         * @param TransactionSplit $splitEntry
         * @param BankAccount $bankAccount
         * @param boolean $isAdd
         * @return Transactions
         */
    private function inputTransaction(TransactionSplit $splitEntry, BankAccount $bankAccount, bool $isAdd): Transactions
    {
        if ($isAdd) {
            do {
                $valid = true;
                $splitEntry->transaction_id = $this->viewTransactionId($bankAccount->iban);
                $transaction = Transactions::find($splitEntry->transaction_id);
            } while (!$valid); //todo phpstan
        } else {
            $transaction = Transactions::where('id', $splitEntry->transaction_id)
            ->select(array_keys(TransactionList::$compactView))
            ->first();
            $this->tableConsolePagination(
                new Collection([$transaction]),
                TransactionList::$compactView,
                null,
                'cli.transaction.base.table.header.'
            );
        }

        return $transaction;
    }

    /**
     *
     * @param TransactionSplit $splitEntry
     * @param boolean $isAdd
     * @return BankAccount
     */
    private function inputBankAccount(TransactionSplit $splitEntry, bool $isAdd): BankAccount
    {
        if ($isAdd) {
            $bankAccountId = $this->viewAccountId();
        } else {
            $transaction = Transactions::find($splitEntry->transaction_id);
            $bankAccountId = BankAccount::where('iban', $transaction->bank_account_iban)->value('id');
        }

        $bankAccount = BankAccount::find($bankAccountId);
        info(__('cli.base.iban') . ': ' . $bankAccount->iban);
        return $bankAccount;
    }

    /**
     *
     * @param Transactions $transaction
     * @param float $totalSplittedAmount
     * @param float|null $rawAmount
     * @param boolean $isAdd
     * @return float|null
     */
    private function inputAmount(
        Transactions $transaction,
        float $totalSplittedAmount,
        ?float $rawAmount,
        bool $isAdd
    ): ?float {
        do {
            $valid = true;

            if (!$isAdd && !$this->validateTotalAmountExceeded($transaction->amount, $totalSplittedAmount, 0.01)) {
                $this->emptyLn();
                $this->error(__('cli.transaction_split.upsert.validate_error.no_more_split_allowed'));
                return null;
            }

            $amount  = $this->viewInput(
                __('cli.transaction_split.upsert.new_amount'),
                'required|numeric|regex:/^\d+(\.\d{2})?$/',
                $amount ?? $rawAmount,
                self::VALUE_TYPE_DECIMAL
            );

            $isValidTotalAmountExceeded = $this->validateTotalAmountExceeded(
                $transaction->amount,
                $isAdd ? $totalSplittedAmount : ($totalSplittedAmount - $rawAmount),
                $amount
            );

            if (!$isValidTotalAmountExceeded) {
                $valid = false;
                $rest = (abs($transaction->amount) - ($isAdd ?
                        $totalSplittedAmount :
                        ($totalSplittedAmount - $rawAmount + $amount)
                    )
                );
                $this->emptyLn();
                $this->error(
                    __(
                        'cli.transaction_split.upsert.validate_error.total_amount_exceeded',
                        [
                            'rest' => $rest
                        ]
                    )
                );
            }
        } while (!$valid);

        return $amount;
    }

    /**
     *
     * @param float $totalAmount
     * @param float $totalSplittedAmount
     * @param float $newAmount
     * @return boolean
     */
    private function validateTotalAmountExceeded(
        float $totalAmount,
        float $totalSplittedAmount,
        float $newAmount = 0
    ): bool {
        return (abs($totalAmount) - $totalSplittedAmount - $newAmount) > 0;
    }
}
