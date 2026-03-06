<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Console\OutputStyle;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait InitViewIO
{
    /**
     * Initializes the view.
     *
     * @return void
     */
    protected function initViewIO(): void
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
