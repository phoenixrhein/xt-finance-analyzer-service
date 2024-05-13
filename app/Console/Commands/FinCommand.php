<?php

namespace de\xovatec\financeAnalyzer\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Traits\TableConsolePagination;

abstract class FinCommand extends Command
{
    use TableConsolePagination;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        if (Str::startsWith($this->description, 'cli.')) {
            $this->description = __($this->description);
        }
        parent::__construct();
    }

    /**
     * View header layout
     *
     * @return void
     */
    private function header(): void
    {
        $appName = '<bg=green;fg=black> FIN </> <fg=green>Finance Analyzer</>';

        $this->line('<fg=green>/</><fg=gray>/</><fg=green>/</><fg=gray>/</><fg=green>/</>');
        $this->line('<fg=gray>/</><fg=green>/</><fg=gray>/</><fg=green>/</> <fg=green>█ █</> <fg=gray>▀▀█▀▀</>');
        $this->line('<fg=green>/</><fg=gray>/</><fg=green>/</>  <fg=green> █</>  <fg=gray>  █</>');
        $this->line('<fg=gray>/</><fg=green>/</>   <fg=green>█ █</> <fg=gray>  █</>   ' . $appName);
        $this->line('<fg=green>/</>');
        $this->line('<bg=green;fg=black> .: ' . $this->description . ' :. </>');
    }

    /**
     * Process the console command
     *
     * @return void
     */
    abstract protected function process(): void;

    /**
     * Handle console command
     *
     * @return void
     */
    final public function handle()
    {
        $this->header();
        $this->process();
    }
}
