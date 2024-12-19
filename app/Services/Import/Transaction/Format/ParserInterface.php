<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Transaction\Format;

interface ParserInterface
{
    /**
     *
     * @param string $filePath
     * @param int $lastMonths
     * @return array
     */
    public function parse(string $filePath, int $lastMonths): array;
}
