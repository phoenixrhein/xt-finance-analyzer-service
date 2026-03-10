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
    final public function __construct()
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        if (Str::contains($this->signature, '[:')) {
            $this->signature = Str::replaceMatches(
                '/\[:([A-Za-z._])+:\]/',
                function (array $matches) {
                    $key = Str::replace(['[:', ':]'], '', $matches[0]);
                    return app()->bound('translator') ? __($key) : $key;
                },
                $this->signature
            );
        }

        if (Str::startsWith($this->description, 'cli.')) {
            $this->description = app()->bound('translator') ? __($this->description) : $this->description;
        }
    }

    /**
     * View header layout
     *
     * @return void
     */
    private function header(): void
    {
        $appName = '<bg=green;fg=black> FIN </> <fg=green>Finance Analyzer</> <fg=gray>' . config('app.version') . '</>';

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
        if (method_exists($this, 'init')) {
            $this->laravel->call([$this, 'init']);
        }
        $method = method_exists($this, 'process') ? 'process' : '__invoke';
        $returnValue = (int) $this->laravel->call([$this, $method]);
        $this->emptyLn();
        return $returnValue;
    }
}
