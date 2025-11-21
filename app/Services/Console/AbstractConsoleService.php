<?php

namespace de\xovatec\financeAnalyzer\Services\Console;

use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use de\xovatec\financeAnalyzer\Traits\Command\View\BaseView;

abstract class AbstractConsoleService
{
    use InteractsWithIO;
    use BaseView;
    
    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(?InputInterface $input = null, ?OutputInterface $output = null)
    {
        if ($input !== null && $output !== null) {
            $this->setInput($input);
            $this->setOutput($output);
        }
    }
}
