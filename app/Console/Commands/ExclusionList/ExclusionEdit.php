<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\ExclusionList;

use de\xovatec\financeAnalyzer\Models\ExclusionList;
use de\xovatec\financeAnalyzer\Traits\Command\View\IbanInput;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\View\SelectAccountId;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class ExclusionEdit extends AbstractExclusionList implements ProvidesAccountListQueryInterface
{
    use IbanInput;
    use SelectAccountId;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:exclusion-upsert {exclusionId? : [:cli.exclusion_list.base.param.exclusion_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.exclusion_list.upsert.description';

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
        $exclusionId = (int)$this->argument('exclusionId');
        $isAdd = $exclusionId <= 0;
        if ($isAdd) {
            warning(__('cli.base.upsert_hint_add'));
            $exclusionEntry = new ExclusionList();
        } else {
            $exclusionEntry = ExclusionList::find($exclusionId);

            if (!$exclusionEntry instanceof ExclusionList) {
                $this->emptyLn();
                $this->error(__('cli.base.error.not_found', ['id' => $exclusionId]));
                return;
            }
        }

        $exclusionEntry->bank_account_id = $this->viewAccountId($exclusionEntry->bank_account_id);
        $exclusionEntry->value = $this->viewIbanInput($exclusionEntry->value ?? '', false);
        $exclusionEntry->type = ExclusionList::TYPE_IBAN;
        $exclusionEntry->comment = text(
            label: __('cli.exclusion_list.upsert.comment'),
            default: $exclusionEntry->comment ?? ''
        );

        $labelKey = 'cli.base.created';
        if (!$isAdd) {
            $labelKey = 'cli.base.edited';
        }

        $exclusionEntry->save();
        $this->info(__($labelKey, ['id' => $exclusionEntry->id]));
    }
}
