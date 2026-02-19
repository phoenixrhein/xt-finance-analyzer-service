<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Console\Concerns\InteractsWithIO;

trait InteractsWithIOExtended
{
    use InteractsWithIO;

    /**
     *
     * @param string $string
     * @return void
     */
    public function infoInLn(string $string): void
    {
        $this->output->write('<info>'.$string.'</info>');
    }

    /**
     *
     * @return void
     */
    public function separatorLine(): void
    {
        $this->comment('—————————————————————————————————————————————————————————————————————————————————————————————————');
    }
}
