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
    protected $signature = 'fin:ignore-delete {ignoreId : [:cli.ignore_list.base.param.ignore_list:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.ignore_list.delete.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $ignoreId = $this->argument('ignoreId');
        $ignoreEntry = IgnoreList::find($ignoreId);
        if (!$ignoreEntry instanceof IgnoreList) {
            $this->emptyLn();
            $this->error(__('cli.ignore_list.upsert.error.not_found', ['ignoreId' => $ignoreId]));
            return;
        }

        if ($this->confirmPrompt(__('cli.ignore_list.delete.confirm', ['id' => $ignoreId, 'comment' => $ignoreEntry->comment])) === false) {
            return;
        }

        $ignoreEntry->delete();
        $this->info(__('cli.base.deleted', ['id' => $ignoreId]));
    }
}
