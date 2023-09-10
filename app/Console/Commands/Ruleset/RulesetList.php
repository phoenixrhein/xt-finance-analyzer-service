<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Ruleset;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Services\RulesetService;

class RulesetList extends Command
{
    public function __construct(private RulesetService $rulesetService)
    {
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ruleset-list';

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
            Arr::map($this->rulesetService->getMainRulesetsWithExpression(), function (array $value) {
                $category = $value['actions']['category']['name'] . ' [' . $value['actions']['category']['id'] . ']';
                return Arr::only($value, ['id', 'name', 'expression'])+ [$category];
            })
        );
    }
}
