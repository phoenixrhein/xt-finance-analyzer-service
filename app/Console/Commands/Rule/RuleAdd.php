<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use de\xovatec\financeAnalyzer\Services\Expression\ExpressionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionSyntaxParser;
use Throwable;

class RuleAdd extends Command
{
    /**
     * @param ExpressionSyntaxParser $expressionParser
     * @param ExpressionService $expressionService
     */
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
    protected $signature = 'fin:rule-add {name} {categoryId} {expression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new rule expression';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            $ruleData = $this->expressionParser->parse($this->argument('expression'));
            DB::beginTransaction();
            $id = $this->expressionService->saveRuleExpression(
                $this->argument('name'),
                (int)$this->argument('categoryId'),
                $ruleData
            );
            DB::commit();
            $this->info("Rule with id '{$id}' created");
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }
}
