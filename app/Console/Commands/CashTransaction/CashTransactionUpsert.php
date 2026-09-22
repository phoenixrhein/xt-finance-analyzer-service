<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\CashTransaction;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use de\xovatec\financeAnalyzer\Enums\CurrencyCode;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\CashTransaction;
use de\xovatec\financeAnalyzer\Services\CashTransactionValidationService;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use de\xovatec\financeAnalyzer\Traits\Command\View\SelectAccountId;
use de\xovatec\financeAnalyzer\Traits\Command\View\FindAndSelectTransaction;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class CashTransactionUpsert extends FinCommand implements ProvidesAccountListQueryInterface
{
    use SelectAccountId;
    use SimpleInput;
    use FindAndSelectTransaction;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cash-upsert {cashTransactionId? : ' .
        '[:cli.cash_transaction.base.param.cash_transaction_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.cash_transaction.upsert.description';

    /**
     *
     * @var AccountListQuery
     */
    protected AccountListQuery $accountListQuery;

    protected CashTransactionValidationService $cashTransactionValidationService;

    /**
     *
     * @param AccountListQuery $accountlistQuery
     */
    public function init(
        AccountListQuery $accountlistQuery,
        CashTransactionValidationService $cashTransactionValidationService
    ): void
    {
        $this->accountListQuery = $accountlistQuery;
        $this->cashTransactionValidationService = $cashTransactionValidationService;
    }
    /**
     *
     * @return AccountListQuery
     */
    public function getAccountListQuery(): AccountListQuery
    {
        return $this->accountListQuery;
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $valid = true;
        $cashTransactionEntry = null;
        do {
            $cashTransactionId = (int)$this->argument('cashTransactionId');
            $isAdd = $cashTransactionId <= 0;
            if ($valid !== false) {
                if ($isAdd) {
                    $cashTransactionEntry = new CashTransaction();
                    warning(__('cli.base.upsert_hint_add'));
                    $cashTransactionEntry->bank_account_id = $this->viewAccountId(
                        $cashTransactionEntry->bank_account_id
                    );
                    $cashTransactionEntry->cash_booking_date = Carbon::now();
                } else {
                    $cashTransactionEntry = CashTransaction::find($cashTransactionId);
                    if (!$cashTransactionEntry instanceof CashTransaction) {
                        $this->emptyLn();
                        $this->error(__('cli.base.error.not_found', ['id' => $cashTransactionId]));
                        return;
                    }
                    $cashTransactionEntry->cash_booking_date = Carbon::createFromFormat(
                        'Y-m-d',
                        $cashTransactionEntry->cash_booking_date
                    );
                }
            }
            $valid = true;

            $bankAccount = BankAccount::find($cashTransactionEntry->bank_account_id);
            info(__('cli.base.iban') . ': ' . $bankAccount->iban);
            $cashTransactionEntry->transaction_id = $this->viewOptionalTransactionId(
                $bankAccount->iban,
                $cashTransactionEntry->transaction_id
            );
            $cashTransactionEntry->amount = $this->viewInput(
                __('cli.cash_transaction.upsert.amount'),
                'required|numeric|gt:0|regex:/^\d+(\.\d{2})?$/',
                $cashTransactionEntry->amount,
                self::VALUE_TYPE_DECIMAL
            );
            $cashTransactionEntry->currency = $this->viewInput(
                __('cli.cash_transaction.upsert.currency'),
                ['required', Rule::enum(CurrencyCode::class)],
                $cashTransactionEntry->currency ?? CurrencyCode::EUR->value
            );
            $cashTransactionEntry->cash_booking_date  = $this->viewInput(
                __('cli.cash_transaction.upsert.cash_booking_date'),
                ['required', 'date_format:d.m.Y'],
                Carbon::parse($cashTransactionEntry->cash_booking_date)->format('d.m.Y')
            );
            $cashTransactionEntry->note  = $this->viewInput(
                __('cli.cash_transaction.upsert.note'),
                ['required'],
                $cashTransactionEntry->note
            );

            $validationError = $this->cashTransactionValidationService->validate($cashTransactionEntry);
            if ($validationError !== null) {
                $this->error($validationError);
                $valid = false;
                continue;
            }

            if (!$this->confirmPrompt(__('cli.base.confirm_save'))) {
                $valid = false;
            }
        } while (!$valid);

        $labelKey = 'cli.base.created';
        if (!$isAdd) {
            $labelKey = 'cli.base.edited';
        }
        $cashTransactionEntry->cash_booking_date = Carbon::createFromFormat(
            'd.m.Y',
            $cashTransactionEntry->cash_booking_date
        );
        $cashTransactionEntry->save();
        $this->info(__($labelKey, ['id' => $cashTransactionEntry->id]));
    }
}
