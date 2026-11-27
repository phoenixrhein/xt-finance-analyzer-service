<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use Illuminate\Support\Arr;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Services\Rule\RuleListService;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;

class RuleList extends FinCommand
{
    use BankAccountIdParameter;

    /**
     *
     * @var RuleListService
     */
    protected RuleListService $ruleListService;

    /**
     *
     * @param RuleListService $ruleListService
     */
    public function init(RuleListService $ruleListService): void
    {
        $this->ruleListService = $ruleListService;
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
    public function process(): void
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
                __('cli.rule.list.table_header.income_category'),
                __('cli.rule.list.table_header.outcome_category')
            ],
            Arr::map($this->ruleListService->getRulesWithExpression($bankAccount->id), function (array $value) {
                $categories = collect($value['actions'])
                    ->map(function (array $action): array {
                        $action['cashflow_type'] ??= Category::findOrFail($action['category_id'])
                            ->getCashflowType()
                            ->name;
                        return $action;
                    })
                    ->keyBy('cashflow_type');
                $formatCategory = static function ($action): string {
                    if ($action === null) {
                        return '';
                    }

                    $category = $action['category'];
                    return $category['name'] . ' [' . $category['id'] . ']';
                };

                return Arr::only($value, ['id', 'name', 'expression']) + [
                    $formatCategory($categories->get('in')),
                    $formatCategory($categories->get('out')),
                ];
            })
        );
    }
}
