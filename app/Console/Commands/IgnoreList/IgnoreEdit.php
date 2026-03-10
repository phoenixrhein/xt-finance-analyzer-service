<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Traits\Command\View\IbanInput;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\View\SelectAccountId;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class IgnoreEdit extends AbstractIgnoreList implements ProvidesAccountListQueryInterface
{
    use IbanInput;
    use SelectAccountId;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ignore-upsert {ignoreId? : [:cli.ignore_list.base.param.ignore_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.ignore_list.upsert.description';

    /**
     *
     * @var AccountListQuery
     */
    protected AccountListQuery $accountListQuery;

    /**
     *
     * @param AccountListQuery $accountlistQuery
     */
    public function init(AccountListQuery $accountlistQuery): void
    {
        $this->accountListQuery = $accountlistQuery;
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
        $ignoreId = (int)$this->argument('ignoreId');
        $isAdd = $ignoreId <= 0;
        if ($isAdd) {
            warning(__('cli.base.upsert_hint_add'));
            $ignoreEntry = new IgnoreList();
        } else {
            $ignoreEntry = IgnoreList::find($ignoreId);

            if (!$ignoreEntry instanceof IgnoreList) {
                $this->emptyLn();
                $this->error(__('cli.base.error.not_found', ['id' => $ignoreId]));
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
