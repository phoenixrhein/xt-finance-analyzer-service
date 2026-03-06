<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\CashDeposit;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use de\xovatec\financeAnalyzer\Enums\CurrencyCode;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\CashDeposit;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use de\xovatec\financeAnalyzer\Traits\Command\View\SelectAccountId;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class CashDepositUpsert extends FinCommand implements ProvidesAccountListQueryInterface
{
    use SelectAccountId;
    use SimpleInput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cash-upsert {cashDepositId? : [:cli.cash_deposit.base.param.cash_deposit_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.cash_deposit.upsert.description';

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
    public function getAccountListQuery(): AccountListQuery
    {
        return $this->accountlistQuery;
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $valid = true;
        do {
            $cashDepositId = (int)$this->argument('cashDepositId');
            $isAdd = $cashDepositId <= 0;
            if ($valid !== false) {
                if ($isAdd) {
                    $cashDepositEntry = new CashDeposit();
                    warning(__('cli.base.upsert_hint_add'));
                    $cashDepositEntry->bank_account_id = $this->viewAccountId($cashDepositEntry->bank_account_id);
                    $cashDepositEntry->deposit_date = Carbon::now();
                } else {
                    $cashDepositEntry = CashDeposit::find($cashDepositId);
                    $cashDepositEntry->deposit_date = Carbon::createFromFormat(
                        'Y-m-d',
                        $cashDepositEntry->deposit_date
                    );
                    if (!$cashDepositEntry instanceof CashDeposit) {
                        $this->emptyLn();
                        $this->error(__('cli.base.error.not_found', ['id' => $cashDepositId]));
                        return;
                    }
                }
            }
            $valid = true;

            $bankAccount = BankAccount::find($cashDepositEntry->bank_account_id);
            info(__('cli.base.iban') . ': ' . $bankAccount->iban);
            $cashDepositEntry->amount = $this->viewInput(
                __('cli.cash_deposit.upsert.amount'),
                'required|numeric|regex:/^\d+(\.\d{2})?$/',
                $cashDepositEntry->amount,
                self::VALUE_TYPE_DECIMAL
            );
            $cashDepositEntry->currency = $this->viewInput(
                __('cli.cash_deposit.upsert.currency'),
                ['required', Rule::enum(CurrencyCode::class)],
                $cashDepositEntry->currency ?? CurrencyCode::EUR->value
            );
            $cashDepositEntry->deposit_date  = $this->viewInput(
                __('cli.cash_deposit.upsert.deposit_date'),
                ['required', 'date_format:d.m.Y'],
                $cashDepositEntry->deposit_date->format('d.m.Y')
            );
            $cashDepositEntry->note  = $this->viewInput(
                __('cli.cash_deposit.upsert.note'),
                ['required'],
                $cashDepositEntry->note
            );

            if (!$this->confirmPrompt(__('cli.base.confirm_save'))) {
                $valid = false;
            }
        } while (!$valid);

        $labelKey = 'cli.base.created';
        if (!$isAdd) {
            $labelKey = 'cli.base.edited';
        }
        $cashDepositEntry->deposit_date = Carbon::createFromFormat('d.m.Y', $cashDepositEntry->deposit_date);
        $cashDepositEntry->save();
        $this->info(__($labelKey, ['id' => $cashDepositEntry->id]));
    }
}
