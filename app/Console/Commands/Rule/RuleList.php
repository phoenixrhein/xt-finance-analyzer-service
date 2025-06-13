<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use Illuminate\Support\Arr;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\Rule\RuleListService;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class RuleList extends FinCommand
{
    use BankAccountIdParameter;

    /**
     *
     * @param RuleListService $ruleListService
     */
    public function __construct(private RuleListService $ruleListService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-list {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.rule.list.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'), true);
        if (!$bankAccount instanceof BankAccount) {
            return;
        }
        $this->table(
            [
                __('cli.rule.list.table_header.id'),
                __('cli.rule.list.table_header.name'),
                __('cli.rule.list.table_header.expression'),
                __('cli.rule.list.table_header.category')
            ],
            Arr::map($this->ruleListService->getRulesWithExpression($bankAccount->id), function (array $value) {
                $category = $value['actions']['category']['name'] . ' [' . $value['actions']['category']['id'] . ']';
                return Arr::only($value, ['id', 'name', 'expression']) + [$category];
            })
        );
    }
}
