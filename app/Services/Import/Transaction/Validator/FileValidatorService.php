<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Transaction\Validator;

use de\xovatec\financeAnalyzer\Exceptions\FinErrorException;

class FileValidatorService
{
    /**
     *
     * @param string $filepath
     * @return void
     */
    public function validate(string $filepath)
    {
        if (!file_exists($filepath)) {
            throw new FinErrorException(
                'File does not exist: ' . $filepath,
                'msgctx.transaction.import.validate.file_not_found',
                ['file' => $filepath]
            );
        }
        
        if (!is_file($filepath)) {
            throw new FinErrorException(
                'Is no file: ' . $filepath,
                'msgctx.transaction.import.validate.is_no_file',
                ['file' => $filepath]
            );
        }

        if (!is_readable($filepath)) {
            throw new FinErrorException(
                'File is not readable: ' . $filepath,
                'msgctx.transaction.import.validate.file_not_readable',
                ['file' => $filepath]
            );
        }
    }
}
