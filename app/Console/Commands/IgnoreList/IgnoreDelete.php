<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\IgnoreList;

class IgnoreDelete extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ignore-delete {ignoreId : [:cli.ignore_list.base.param.ignore_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.ignore_list.delete.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $ignoreId = $this->argument('ignoreId');
        $ignoreEntry = IgnoreList::find($ignoreId);
        if (!$ignoreEntry instanceof IgnoreList) {
            $this->emptyLn();
            $this->error(__('cli.base.error.not_found', ['id' => $ignoreId]));
            return;
        }

        if (
            $this->confirmPrompt(
                __('cli.ignore_list.delete.confirm', ['id' => $ignoreId, 'comment' => $ignoreEntry->comment])
            ) === false
        ) {
            return;
        }

        $ignoreEntry->delete();
        $this->info(__('cli.base.deleted', ['id' => $ignoreId]));
    }
}
