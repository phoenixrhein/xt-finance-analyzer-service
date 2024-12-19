<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Transaction\Detector;

use League\Csv\Reader;
use InvalidArgumentException;
use de\xovatec\financeAnalyzer\Services\Import\Transaction\Format\ParserInterface;
use de\xovatec\financeAnalyzer\Services\Import\Transaction\Format\Csv\CsvParserInterface;

class CSVFormatDetector implements FileFormatDetectorInterface
{
    /**
     *
     * @var array
     */
    private array $parsers;

    /**
     *
     * @var array
     */
    private array $allowedMimeTypes = [
        'text/plain',
        'text/csv'
    ];

    /**
     *
     * @param array $parsers
     */
    public function __construct(array $parsers)
    {
        foreach ($parsers as $parser) {
            if (!$parser instanceof CsvParserInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Parser must be an instance of %s, %s given.',
                    CsvParserInterface::class,
                    is_object($parser) ? get_class($parser) : gettype($parser)
                ));
            }
        }

        $this->parsers = $parsers;
    }

    /**
     *
     * @param string $filePath
     * @return boolean
     */
    public function supports(string $filePath): bool
    {
        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'CSV') {
            return false;
        }

        if (!in_array(mime_content_type($filePath), $this->allowedMimeTypes)) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param string $filePath
     * @return ParserInterface
     */
    public function getParser(string $filePath): ParserInterface
    {
        $csv = Reader::createFromPath($filePath);
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        $headers = $csv->getHeader();

        foreach ($this->parsers as $parser) {
            if ($parser->supportsHeader($headers)) {
                return $parser;
            }
        }

        throw new InvalidArgumentException('No supported CSV parser found.');
    }
}
