<?php

namespace de\xovatec\financeAnalyzer\Traits;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Helper\TableSeparator;

trait TableConsolePagination
{
    /**
     *
     * @param Collection $transactions
     * @param array $columns
     * @param integer $limit|null
     * @param array $columsLengthConfig
     * @return void
     */
    private function tableConsolePagination(
        Collection $transactions,
        array $columns,
        int $limit = null,
        array $columsLengthConfig = []
    ): void {
        $limit = $limit ?? $transactions->count();
        for ($i = 0; $i < $transactions->count(); $i = $i + $limit) {
            $rows = $transactions->slice($i, $limit);
            $this->table(
                $columns,
                $this->prepareRows($rows, $columsLengthConfig)
            );

            if ($i + $limit < $transactions->count()) {
                $this->ask('Press Enter to continue...');
            }
        }
    }

    /**
     *
     * @param Collection $rows
     * @param array $columsLengthConfig
     * @return array
     */
    private function prepareRows(Collection $rows, array $columsLengthConfig): array
    {
        $newRows = [];
        foreach ($rows as $row) {
            $newRows = array_merge($newRows, $this->prepareRow($row, $columsLengthConfig));
        }
        array_pop($newRows);
        return $newRows;
    }

    /**
     *
     * @param Model $row
     * @param array $columsLengthConfig
     * @return array
     */
    private function prepareRow(Model $row, array $columsLengthConfig): array
    {
        $maxRows = 1;
        $splittedRows = [];
        foreach ($row->toArray() as $column => $value) {
            $splittedRows[$column] = str_split(
                $value,
                $columsLengthConfig[$column] ?? strlen($value) ?: 1
            );
            $maxRows = max($maxRows, count($splittedRows[$column]));
        }
        $newRows = [];
        for ($i = 0; $i < $maxRows; $i++) {
            foreach ($row->toArray() as $column => $value) {
                $newRows[$i][] = $splittedRows[$column][$i] ?? '';
            }
        }
        $newRows[] = new TableSeparator();
        return $newRows;
    }

    /**
     *
     * @param  array  $headers
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $rows
     * @param  \Symfony\Component\Console\Helper\TableStyle|string  $tableStyle
     * @param  array  $columnStyles
     * @return void
     */
    abstract public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = []);
}
