<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\IgnoreList;

class IgnoreList extends AbstractIgnoreList
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:ignore-list  {accountId : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.ignore_list.list.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $this->displayIgnoreList((int)$this->argument('accountId'));
    }
}
