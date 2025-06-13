<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Symfony\Component\Console\Exception\RuntimeException;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Helper\TableSeparator;
use de\xovatec\financeAnalyzer\Traits\Command\View\TableView;

trait TableConsolePagination
{
    use TableView;
    use Halt;

    /**
     *
     * @param Collection $transactions
     * @param array $columnsConfig
     * @param integer $limit|null
     * @param string|null $headlinePrefix
     * @return void
     */
    protected function tableConsolePagination(
        Collection $transactions,
        array $columnsConfig,
        ?int $limit = null,
        ?string $headlinePrefix = null
    ): void {
        $limit = $limit ?? $transactions->count();
        for ($i = 0; $i < $transactions->count(); $i = $i + $limit) {
            $rows = $transactions->slice($i, $limit);
            $this->viewTable(
                $this->getHeadlines($columnsConfig, $headlinePrefix),
                $this->prepareRows($rows, $this->getWidth($columnsConfig)),
                array_values($this->getWidth($columnsConfig))
            );

            if ($i + $limit < $transactions->count()) {
                $this->halt();
            }
        }
    }

    /**
     *
     * @param Collection $rows
     * @param array $columnsLengthConfig
     * @return array
     */
    private function prepareRows(Collection $rows, array $columnsLengthConfig): array
    {
        $newRows = [];
        foreach ($rows as $row) {
            $newRows = array_merge($newRows, $this->prepareRow($row, $columnsLengthConfig));
        }
        array_pop($newRows);
        return $newRows;
    }

    /**
     *
     * @param Model $row
     * @param array $columnsLengthConfig
     * @return array
     */
    private function prepareRow(Model $row, array $columnsLengthConfig): array
    {
        $maxRows = 1;
        $splittedRows = [];
        $columnValueTag = [];
        $pattern = '/^(<[^>]+>)(.*?)(<\/[^>]+>|<\/>)?$/';
        foreach ($row->toArray() as $column => $value) {
            if (preg_match($pattern, $value, $matches)) {
                $columnValueTag[$column] = [
                    'start' => $matches[1],
                    'end' => !empty($matches[3]) ? $matches[3] : "</>",
                ];
                $value = $matches[2];
            } else {
                $columnValueTag[$column] = [];
            }

            $splittedRows[$column] = str_split(
                $value,
                $columnsLengthConfig[$column] ?? strlen($value) ?: 1
            );
            $maxRows = max($maxRows, count($splittedRows[$column]));
        }
        $newRows = [];
        for ($i = 0; $i < $maxRows; $i++) {
            foreach ($row->toArray() as $column => $rawValue) {
                $value = $splittedRows[$column][$i] ?? '';
                if (count($columnValueTag[$column]) > 0 && strlen($value) > 0) {
                    $value = $columnValueTag[$column]['start'] . $value . $columnValueTag[$column]['end'];
                }

                $newRows[$i][] = $value;
            }
        }
        $newRows[] = new TableSeparator();
        return $newRows;
    }

    /**
     *
     * @param array $config
     * @param string|null $headlinePrefix
     * @return array
     */
    private function getHeadlines(array $config, ?string $headlinePrefix = null): array
    {
        return array_map(function ($item, $key) use ($headlinePrefix) {
            if (is_array($item) && array_key_exists('headline', $item)) {
                return $item['headline'];
            }
            return $headlinePrefix === null ? $key : __($headlinePrefix . $key);
        },
        $config, array_keys($config));
    }

    /**
     *
     * @param array $config
     * @return array
     */
    private function getWidth(array $config): array
    {
        $fixedWidths = 0;
        $percentageWidths = [];
        $totalWidth = exec('tput cols') - (count($config) * 3) - 7;

        // First iteration: Sum fixed widths and collect percentage values
        foreach ($config as $column) {
            if (isset($column['width'])) {
                if (is_numeric($column['width'])) {
                    $fixedWidths += $column['width'];
                } elseif (strpos($column['width'], '%') !== false) {
                    $percentageWidths[] = $column['width'];
                }
            }
        }

        // Check if percentage values sum up to 100%
        $totalPercentage = array_sum(array_map(function ($item) {
            return (int) rtrim($item, '%');
        }, $percentageWidths));

        if ($totalPercentage > 100) {
            throw new RuntimeException("Percentages exceed 100%");
        }

        // Calculate remaining width for percentage columns
        $remainingWidth = $totalWidth - $fixedWidths;

        if ($remainingWidth < 0) {
            throw new RuntimeException("Fixed column widths exceed the total width");
        }

        // Convert percentage values to fixed widths
        $calculatedWidths = [];
        foreach ($config as $columnKey => $column) {
            if (isset($column['width'])) {
                if (is_numeric($column['width'])) {
                    // Use the fixed width as is
                    $calculatedWidths[$columnKey] = $column['width'];
                } elseif (strpos($column['width'], '%') !== false) {
                    // Convert percentage to fixed width
                    $percentage = (int) rtrim($column['width'], '%');
                    $calculatedWidths[$columnKey] = round(($percentage / 100) * $remainingWidth);
                }
            }
        }

        return $calculatedWidths;
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
