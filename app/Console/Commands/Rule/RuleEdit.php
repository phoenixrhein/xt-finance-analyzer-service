<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use de\xovatec\financeAnalyzer\Models\Rule;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionService;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionSyntaxParser;
use Throwable;

class RuleEdit extends Command
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
    protected $signature = 'fin:rule-edit {ruleId} {--name=} {--categoryId=} {--expression=}';

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
        $ruleId = $this->argument('ruleId');
        $rule = Rule::findOrFail($ruleId);
        if (strlen(implode('', array_values($this->options()))) === 0) {
            $this->alert("No option for update");
            exit();
        }
        $name = $this->option('name') ?? $rule->name;
        $categoryId = $this->option('categoryId') ?? $rule->actions->category_id;
        if ($this->option('name')) {
            $rule->name = $this->option('name');
            $rule->save();
        }
        if ($this->option('categoryId')) {
            $rule->actions->category_id = $this->option('categoryId');
            $rule->actions->save();
        }

        try {
            if ($this->option('expression')) {
                DB::beginTransaction();
                $rule->forceDelete();
                // in parse koennen Exception geworfen werden
                // wie soll damit umgegangen werden
                // benutzerfreundliche Meldungen
                // suche mit 'Exception(' in app/**
                $ruleData = $this->expressionParser->parse($this->option('expression'));
                $this->expressionService->saveRuleExpression(
                    $name,
                    $categoryId,
                    $ruleData
                );
                DB::commit();
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
        $this->info('rule updated'); //Hinweis: Bei Expression bekommt der Datensatz eine neue ID
    }
}
