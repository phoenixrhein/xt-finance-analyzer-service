<?php

namespace de\xovatec\financeAnalyzer\Services\Console;

use RuntimeException;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use de\xovatec\financeAnalyzer\Traits\Command\View\BaseView;

abstract class AbstractIOService
{
    use InteractsWithIO;
    use BaseView;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!app()->runningInConsole()) {
            throw new RuntimeException('This action can only be executed in the console.');
        }

        $output = app()->bound(OutputInterface::class)
            ? app(OutputInterface::class)
            : new ConsoleOutput();

        $input = app()->bound(InputInterface::class)
            ? app(InputInterface::class)
            : new ArrayInput([]);

        $io = new OutputStyle($input, $output);
        $this->setOutput($io);
        $this->setInput($input);
    }
}
