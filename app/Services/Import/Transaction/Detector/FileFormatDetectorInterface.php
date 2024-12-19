<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Transaction\Detector;

use de\xovatec\financeAnalyzer\Services\Import\Transaction\Format\ParserInterface;

interface FileFormatDetectorInterface
{
    /**
     *
     * @param string $filePath
     * @return boolean
     */
    public function supports(string $filePath): bool;

    /**
     *
     * @param string $filePath
     * @return ParserInterface
     */
    public function getParser(string $filePath): ParserInterface;
}
