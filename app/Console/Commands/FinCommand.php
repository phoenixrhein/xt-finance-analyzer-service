<?php

namespace de\xovatec\financeAnalyzer\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Traits\Command\View\BaseView;
use de\xovatec\financeAnalyzer\Traits\Command\DateRangeParameter;
use de\xovatec\financeAnalyzer\Traits\Command\View\TableConsolePagination;

abstract class FinCommand extends Command
{
    use TableConsolePagination;
    use BaseView;
    use DateRangeParameter;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        if (Str::contains($this->signature, '[:')) {
            $this->signature = Str::replaceMatches(
                '/\[:([A-Za-z._])+:\]/',
                function (array $matches) {
                    return __(Str::replace(['[:', ':]'], '', $matches[0]));
                },
                $this->signature
            );
        }
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
     * Handle console command
     *
     * @return int
     */
    final public function handle(): int
    {
        $this->header();
        $method = method_exists($this, 'process') ? 'process' : '__invoke';
        $returnValue = (int) $this->laravel->call([$this, $method]);
        $this->emptyLn();
        return $returnValue;
    }
}
