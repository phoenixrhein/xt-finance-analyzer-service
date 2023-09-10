<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Ruleset;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use de\xovatec\financeAnalyzer\Models\Ruleset;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionService;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionSyntaxParser;
use Throwable;

class RulesetEdit extends Command
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
    protected $signature = 'fin:ruleset-edit {rulesetId} {--name=} {--categoryId=} {--expression=}';

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
        $rulesetId = $this->argument('rulesetId');
        $ruleset = Ruleset::findOrFail($rulesetId);
        if (strlen(implode('', array_values($this->options()))) === 0) {
            $this->alert("No option for update");
            exit();
        }
        $name = $this->option('name') ?? $ruleset->name;
        $categoryId = $this->option('categoryId') ?? $ruleset->actions->category_id;
        if ($this->option('name')) {
            $ruleset->name = $this->option('name');
            $ruleset->save();
        }
        if ($this->option('categoryId')) {
            $ruleset->actions->category_id = $this->option('categoryId');
            $ruleset->actions->save();
        }

        try {
            if ($this->option('expression')) {
                DB::beginTransaction();
                $ruleset->forceDelete();
                $rulesetData = $this->expressionParser->parse($this->option('expression'));
                $this->expressionService->saveRulesetExpression(
                    $name,
                    $categoryId,
                    $rulesetData
                );
                DB::commit();
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
        $this->info('ruleset updated');
    }
}
