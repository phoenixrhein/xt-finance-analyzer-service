<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

trait TableView
{
    /**
     *
     * @param array $headers
     * @param array $rows
     * @param array $columnsWidth
     * @param TableStyle|string $tableStyle
     * @return void
     */
    private function viewTable(array $headers, array $rows, array $columnsWidth, TableStyle|string $tableStyle = 'default'): void
    {
        $table = new Table($this->getOutput());

        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }

        $table->setHeaders((array) $headers)->setRows($rows)->setStyle($tableStyle);
        $table->setColumnWidths($columnsWidth);
        $table->render();
    }

    /**
     *
     * @return OutputStyle
     */
    abstract private function getOutput();
}
