<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Ruleset;

use de\xovatec\financeAnalyzer\Models\Ruleset;
use Illuminate\Console\Command;

class RulesetDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ruleset-delete {rulesetId} {--forceDelete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete ruleset';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Do you want to delete?') === false) {
            return;
        }
        $rulesetId = $this->argument('rulesetId');
        if ($this->option('forceDelete')) {
            $ruleset = Ruleset::findOrFail($rulesetId);
            $ruleset->forceDelete();
        } else {
            Ruleset::destroy($rulesetId);
        }

        $this->info(("Deleted bank ruleset with id '{$rulesetId}'"));
    }
}
