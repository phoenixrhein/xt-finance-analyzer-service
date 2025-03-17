<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Services\RuleListService;

class RuleList extends Command
{
    public function __construct(private RuleListService $ruleListService)
    {
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->table(
            ['Id', 'Name', 'Expression', 'Category'],
            Arr::map($this->ruleListService->getRulesWithExpression(), function (array $value) {
                $category = $value['actions']['category']['name'] . ' [' . $value['actions']['category']['id'] . ']';
                return Arr::only($value, ['id', 'name', 'expression']) + [$category];
            })
        );
    }
}
