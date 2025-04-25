<?php

namespace de\xovatec\financeAnalyzer\Traits\Command;

use Illuminate\Database\Eloquent\Builder;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Traits\Command\View\TableConsolePagination;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;
use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use Illuminate\Support\Collection;

trait DisplayInterimTransactionResult
{
    use TableConsolePagination;

    /**
     * @var int
     */
    private int $displayLimit = 5;

    /**
     *
     * @return integer
     */
    private function getDisplayLimit(): int
    {
        return $this->displayLimit;
    }

    /**
     *
     * @param integer $displayLimit
     * @return void
     */
    private function setDisplayLimit(int $displayLimit): void
    {
        $this->displayLimit = $displayLimit;
    }

    /**
     * @return SqlQueryBuilder
     */
    abstract protected function getSqlQueryBuilder(): SqlQueryBuilder;

    /**
     * @param Builder $transactions
     * @param ConditionList $conditions
     * @return bool
     */
    private function displayInterimResult(Builder $transactions, ConditionList $conditions, int $start = 0): bool
    {
        $this->getSqlQueryBuilder()->build(
            $transactions,
            $conditions
        );

        if (empty($transactions->getQuery()->columns)) {
            $transactions->select(array_keys(TransactionList::$compactView));
        }
            
        $totalCount = $transactions->count();

        $this->onTotalResult(CopyBuilderQueryHelper::copy($transactions));

        $transactions->skip($start)
            ->take($this->getDisplayLimit());

        $data = method_exists($this, 'formatData')
            ? $this->formatData($transactions->get())
            : $transactions->get();

        $this->tableConsolePagination(
            $data,
            TransactionList::$compactView,
            null,
            'cli.transaction.base.table.header.'
        );

        $hasMore = false;

        if ($totalCount > ($this->getDisplayLimit() + $start)) {
            $hasMore = true;
            $this->line(
                ($totalCount - ($this->getDisplayLimit() + $start))
                . ' ' . __('cli.view.display_interim_results.more_matches_found')
            );
        } elseif ($totalCount === 0) {
            $this->newLine();
            $this->alert(__('cli.view.display_interim_results.no_matches_found'));
        }

        return $hasMore;
    }

    /**
     *
     * @param Builder $clonedTransactions
     * @return void
     */
    protected function onTotalResult(Builder $clonedTransactions): void
    {
        // can be overridden in child class
    }

    /**
     *
     * @param Collection $data
     * @return Collection
     */
    protected function formatData(Collection $data): Collection
    {
        return $data;
    }
}
