<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Transaction\Format\Csv;

use de\xovatec\financeAnalyzer\Services\Import\Transaction\Format\ParserInterface;

interface CsvParserInterface extends ParserInterface
{
    /**
     *
     * @param array $headers
     * @return boolean
     */
    public function supportsHeader(array $headers): bool;
}
