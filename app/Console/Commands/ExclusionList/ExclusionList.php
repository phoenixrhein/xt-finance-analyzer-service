<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\ExclusionList;

class ExclusionList extends AbstractExclusionList
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:exclusion-list  {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.exclusion_list.list.description';

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $this->displayExclusionList((int)$this->argument('accountId'));
    }
}
