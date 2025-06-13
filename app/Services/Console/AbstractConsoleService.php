<?php

namespace de\xovatec\financeAnalyzer\Services\Console;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;

abstract class AbstractConsoleService
{
    /**
     *
     * @var FinCommand
     */
    private FinCommand $command;

    /**
     *
     * @param FinCommand $command
     * @return void
     */
    public function setIo(FinCommand $command): void
    {
        $this->command = $command;
    }

    /**
     *
     * @return FinCommand
     */
    protected function getIo(): FinCommand
    {
        return $this->command;
    }
}
