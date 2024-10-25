<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\TransactionAdjustment;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\TransactionAdjustment;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use de\xovatec\financeAnalyzer\Traits\Command\View\SelectAccountId;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;
use de\xovatec\financeAnalyzer\Traits\Command\View\FindAndSelectTransaction;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class AdjustmentUpsert extends FinCommand
{
    use SelectAccountId;
    use SimpleInput;
    use FindAndSelectTransaction;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:adjust-upsert {adjustmentId? : ' .
        '[:cli.transaction_adjustment.base.param.transaction_adjustment_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction_adjustment.upsert.description';

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
            $adjustmentId = (int)$this->argument('adjustmentId');
            $isAdd = $adjustmentId <= 0;
            if ($valid !== false) {
                if ($isAdd) {
                    $adjustmentEntry = new TransactionAdjustment();
                    warning(__('cli.base.upsert_hint_add'));
                    $bankAccountId = $this->viewAccountId($adjustmentEntry->bank_account_id);
                    $adjustmentEntry->transaction_date = Carbon::now();
                } else {
                    $adjustmentEntry = TransactionAdjustment::find($adjustmentId);
                    $adjustmentEntry->transaction_date = Carbon::createFromFormat(
                        'Y-m-d',
                        $adjustmentEntry->transaction_date
                    );
                    if (!$adjustmentEntry instanceof TransactionAdjustment) {
                        $this->emptyLn();
                        $this->error(__('cli.base.error.not_found', ['id' => $adjustmentId]));
                        return;
                    }
                    $transaction = Transactions::find($adjustmentEntry->transaction_id);
                    $bankAccountId = BankAccount::where('iban', $transaction->bank_account_iban)->value('id');
                }
            }
            $valid = true;

            $bankAccount = BankAccount::find($bankAccountId);
            info(__('cli.base.iban') . ': ' . $bankAccount->iban);

            if ($isAdd) {
                do {
                    $valid = true;
                    $adjustmentEntry->transaction_id = $this->viewTransactionId($bankAccount->iban);

                    $query = TransactionAdjustment::where('transaction_id', $adjustmentEntry->transaction_id);
                    if ($query->count() !== 0) {
                        $this->emptyLn();
                        $this->error(
                            __(
                                'cli.transaction_adjustment.upsert.validate_error.duplicate',
                                ['id' => $query->first()->id]
                            )
                        );
                        $valid = false;
                    }
                } while (!$valid);
            } else {
                $transaction = Transactions::where('id', $adjustmentEntry->transaction_id)
                ->select(array_keys(TransactionList::$compactView))
                ->first();
                $this->tableConsolePagination(
                    new Collection([$transaction]),
                    TransactionList::$compactView,
                    null,
                    'cli.transaction.base.table.header.'
                );
            }

            $adjustmentEntry->transaction_date  = $this->viewInput(
                __('cli.transaction_adjustment.upsert.new_date'),
                ['required', 'date_format:d.m.Y'],
                $adjustmentEntry->transaction_date->format('d.m.Y')
            );
            $adjustmentEntry->note  = $this->viewInput(
                __('cli.transaction_adjustment.upsert.note'),
                ['required'],
                $adjustmentEntry->note
            );

            if (!$this->confirmPrompt(__('cli.base.confirm_save'))) {
                $valid = false;
            }
        } while (!$valid);

        $labelKey = 'cli.base.created';
        if (!$isAdd) {
            $labelKey = 'cli.base.edited';
        }
        $adjustmentEntry->transaction_date = Carbon::createFromFormat('d.m.Y', $adjustmentEntry->transaction_date);
        $adjustmentEntry->save();
        $this->info(__($labelKey, ['id' => $adjustmentEntry->id]));
    }
}
