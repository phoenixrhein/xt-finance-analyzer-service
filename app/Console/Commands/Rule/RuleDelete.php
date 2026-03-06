<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use de\xovatec\financeAnalyzer\Models\Rule;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\Rule\RefreshTransactionRuleIndexService;

class RuleDelete extends FinCommand
{
    /**
     *
     * @param RefreshTransactionRuleIndexService $refreshService
     */
    public function __construct(private RefreshTransactionRuleIndexService $refreshService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-delete {ruleId : [:cli.base.param.rule_id:]}" .
        " {--forceDelete : [:cli.base.param.force_delete:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.rule.delete.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $ruleId = $this->argument('ruleId');
        $rule = Rule::find($ruleId);
        if ($rule === null) {
            $this->error(__('cli.rule.delete.error.not_found', ['id' => $ruleId]));
            return;
        }

        if ($this->confirmPrompt(__('cli.rule.delete.confirm', ['id' => $this->argument('ruleId')])) === false) {
            return;
        }

        if ($this->option('forceDelete')) {
            $rule->forceDelete();
        } else {
            Rule::destroy($ruleId);
        }

        $this->refreshService->deleteByRuleId($ruleId, $rule->bank_account_id);

        $this->info(__('cli.rule.delete.success', ['ruleId' => $ruleId]));
    }
}
