<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\View\IbanInput;

use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class IgnoreEdit extends AbstractIgnoreList
{
    use IbanInput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ignore-upsert {ignoreId? : [:cli.ignore_list.base.param.ignore_list:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.ignore_list.upsert.description';

    /**
     *
     * @param AccountListQuery $listQuery
     */
    public function __construct(
        private AccountListQuery $listQuery
    ) {
        parent::__construct();
    }

    /**
     *
     * @param integer|null $rawAccountId
     * @return integer
     */
    protected function viewAccountId(int $rawAccountId = null): int
    {
        $accountId = $rawAccountId;
        do {
            $accountId = text(
                label: __('cli.ignore_list.upsert.edit_bank_account_id'),
                default: $accountId ?? ''
            );

            $valid = $this->viewValidatorError(
                [
                    'bank_account_id' => $accountId
                ],
                [
                    'bank_account_id' => IgnoreList::getRules()['bank_account_id']
                ]
            );

            if (!$valid) {
                $this->emptyLn();
                $this->table(
                    [
                        __('cli.account.list.table.columns.id'),
                        __('cli.account.list.table.columns.iban'),
                        __('cli.account.list.table.columns.bic'),
                        __('cli.account.list.table.columns.count_users')
                    ],
                    $this->listQuery->createList()->get()->toArray()
                );
            }
        } while (!$valid);

        return (int)$accountId;
    }

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $ignoreId = (int)$this->argument('ignoreId');
        $isAdd = $ignoreId <= 0;
        if ($isAdd) {
            warning(__('cli.ignore_list.upsert.hint_add'));
            $ignoreEntry = new IgnoreList();
        } else {
            $ignoreEntry = IgnoreList::find($ignoreId);

            if (!$ignoreEntry instanceof IgnoreList) {
                $this->emptyLn();
                $this->error(__('cli.ignore_list.upsert.error.not_found', ['ignoreId' => $ignoreId]));
                return;
            }
        }

        $ignoreEntry->bank_account_id = $this->viewAccountId($ignoreEntry->bank_account_id);
        $ignoreEntry->value = $this->viewIbanInput($ignoreEntry->value ?? '', false);
        $ignoreEntry->type = IgnoreList::TYPE_IBAN;
        $ignoreEntry->comment = text(
            label: __('cli.ignore_list.upsert.comment'),
            default: $ignoreEntry->comment ?? ''
        );

        $labelKey = 'cli.base.created';
        if (!$isAdd) {
            $labelKey = 'cli.base.edited';
        }

        $ignoreEntry->save();
        $this->info(__($labelKey, ['id' => $ignoreEntry->id]));
    }
}
