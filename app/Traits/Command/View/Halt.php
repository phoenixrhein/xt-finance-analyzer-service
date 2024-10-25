<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use function Laravel\Prompts\pause;

trait Halt
{
    /**
     *
     * @return void
     */
    private function halt(): void
    {
        pause(__('cli.base.halt'));
    }
}
