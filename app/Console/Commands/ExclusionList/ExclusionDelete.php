<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\ExclusionList;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\ExclusionList;

class ExclusionDelete extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:exclusion-delete {exclusionId : [:cli.exclusion_list.base.param.exclusion_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.exclusion_list.delete.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $exclusionId = $this->argument('exclusionId');
        $exclusionEntry = ExclusionList::find($exclusionId);
        if (!$exclusionEntry instanceof ExclusionList) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $exclusionId]));
            return;
        }

        if (
            $this->confirmPrompt(
                __('cli.exclusion_list.delete.confirm', ['id' => $exclusionId, 'comment' => $exclusionEntry->comment])
            ) === false
        ) {
            return;
        }

        $exclusionEntry->delete();
        $this->info(__('cli.base.deleted', ['id' => $exclusionId]));
    }
}
