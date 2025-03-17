<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use de\xovatec\financeAnalyzer\Models\Rule;
use Illuminate\Console\Command;

class RuleDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-delete {ruleId} {--forceDelete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete rule';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Do you want to delete?') === false) {
            return;
        }
        $ruleId = $this->argument('ruleId');
        if ($this->option('forceDelete')) {
            $rule = Rule::findOrFail($ruleId); // Meldung, falls Rule nicht existiert
            $rule->forceDelete();
        } else {
            Rule::destroy($ruleId);
        }

        $this->info("Deleted rule with id '{$ruleId}'");
    }
}
