<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

use de\xovatec\financeAnalyzer\Models\IgnoreList;
use Illuminate\Console\Command;

class IgnoreDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ignore-delete {ignoreId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete ignore list entry';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Do you want to delete?') === false) {
            return;
        }
        $ignoreEntry = IgnoreList::findOrFail($this->argument('ignoreId'));
        $ignoreEntry->delete();
        $this->info('Ingore list entry deleted');
    }
}
