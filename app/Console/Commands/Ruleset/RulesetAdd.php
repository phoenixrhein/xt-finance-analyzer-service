<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Ruleset;

use de\xovatec\financeAnalyzer\Services\Expression\ExpressionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionSyntaxParser;
use Throwable;

class RulesetAdd extends Command
{
    public function __construct(
        private ExpressionSyntaxParser $expressionParser,
        private ExpressionService $expressionService
    ) {
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ruleset-add {name} {categoryId} {expression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new ruleset b< expression';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //dfsdfsdf
        //dsfsdfsdf
        try {
            $rulesetData = $this->expressionParser->parse($this->argument('expression'));
            DB::beginTransaction();
            $id = $this->expressionService->saveRulesetExpression(
                $this->argument('name'),
                (int)$this->argument('categoryId'),
                $rulesetData
            );
            DB::commit();
            $this->info("Ruleset with id '{$id}' created");
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }

}
