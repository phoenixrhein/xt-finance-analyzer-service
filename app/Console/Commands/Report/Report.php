<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Report;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;

class Report extends FinCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.report.description';

        /**
     * @inheritDoc
     */
    protected function process(): void
    {
    }
}
