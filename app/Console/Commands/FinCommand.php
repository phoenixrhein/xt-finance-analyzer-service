<?php

namespace de\xovatec\financeAnalyzer\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Traits\TableConsolePagination;

use function Laravel\Prompts\confirm;

abstract class FinCommand extends Command
{
    use TableConsolePagination;

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
    final public function handle(): void
    {
        $this->header();
        $this->process();
        $this->emptyLn();
    }

    /**
     * Write an empty line
     *
     * @return void
     */
    protected function emptyLn(): void
    {
        $this->line('');
    }

    /**
     *
     * @param string $label
     * @param boolean $default
     * @param string $yes
     * @param string $no
     * @param boolean $required
     * @param mixed $validate
     * @param string $hint
     * @return boolean
     */
    protected function confirmPrompt(
        string $label,
        bool $default = true,
        string $yes = '',
        string $no = '',
        bool|string $required = false,
        mixed $validate = null,
        string $hint = ''
    ): bool {
        return confirm(
            $label,
            $default,
            Str::length($yes) ? $yes : __('cli.base.button.yes'),
            Str::length($no) ? $no : __('cli.base.button.no'),
            $required,
            $validate,
            $hint
        );
    }
}
