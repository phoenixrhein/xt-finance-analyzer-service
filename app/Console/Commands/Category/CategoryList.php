<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Category;

use de\xovatec\financeAnalyzer\Models\Cashflow;

class CategoryList extends AbstractCategory
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:cat-list {cashflowId : [:cli.category.base.param.cashflow_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.category.list.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $cashflowId = $this->argument('cashflowId');
        $cashflow = Cashflow::find($cashflowId);
        if (!$cashflow instanceof Cashflow) {
            $this->emptyLn();
            $this->error(__('cli.category.base.error.not_found_cashflow_id', ['cashflowId' => $cashflowId]));
            return;
        }

        $this->emptyLn();
        $this->displayCashflowTrees($cashflow);
    }
}
