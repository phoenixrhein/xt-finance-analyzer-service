<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Transaction\Format\Csv;

use Carbon\Carbon;
use League\Csv\Reader;
use League\Csv\Serializer\Denormalizer;
use de\xovatec\financeAnalyzer\Dto\Import\FileRecord\Camt52V8CsvSetter;

class Camt52V8CsvParser implements CsvParserInterface
{
    private const TRANSACTION_STATUS_PREPARED = 'Umsatz vorgemerkt';

    /**
     *
     * @var array
     */
    private array $headers = [
        "Auftragskonto",
        "Buchungstag",
        "Valutadatum",
        "Buchungstext",
        "Verwendungszweck",
        "Glaeubiger ID",
        "Mandatsreferenz",
        "Kundenreferenz (End-to-End)",
        "Sammlerreferenz",
        "Lastschrift Ursprungsbetrag",
        "Auslagenersatz Ruecklastschrift",
        "Beguenstigter/Zahlungspflichtiger",
        "Kontonummer/IBAN",
        "BIC (SWIFT-Code)",
        "Betrag",
        "Waehrung",
        "Info"
    ];

    /**
     * constructor
     */
    public function __construct()
    {
        $this->registerCommaReplace();
        $this->registerFormatDate();
        $this->registerUtf8Encode();
    }

    /**
     *
     * @param array $headers
     * @return boolean
     */
    public function supportsHeader(array $headers): bool
    {
        return $headers === $this->headers;
    }

    /**
     *
     * @param string $filePath
     * @param integer $lastMonths
     * @return array
     */
    public function parse(string $filePath, int $lastMonths): array
    {
        $csv = Reader::createFromPath($filePath);
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(';');

        $records = [];
        foreach ($csv->getRecordsAsObject(Camt52V8CsvSetter::class) as $row) {
            if ($row->getStatus() === self::TRANSACTION_STATUS_PREPARED) {
                continue;
            }

            $records[] =  $row;
        }

        $records = $this->filterTransactionsByLastMonths($records, $lastMonths);

        return $records;
    }

    /**
     *
     * @return void
     */
    private function registerFormatDate(): void
    {
        Denormalizer::registerAlias('@format_date', 'string', function (?string $value): ?string {
            if (trim($value) === '') {
                return null;
            }

            return Carbon::createFromFormat('d.m.y', $value)->format('Y-m-d');
        });
    }

    /**
     *
     * @return void
     */
    private function registerUtf8Encode(): void
    {
        Denormalizer::registerAlias('@utf8_encode', 'string', function (?string $value): ?string {
            if (trim($value) === '') {
                return null;
            }

            return mb_convert_encoding($value, "UTF-8", "ISO-8859-1");
        });
    }

    /**
     *
     * @return void
     */
    private function registerCommaReplace(): void
    {
        Denormalizer::registerAlias('@replace_comma', 'float', function (?string $value): ?float {
            if (trim($value) === '') {
                return null;
            }

            $normalized = preg_replace('/[^0-9,-.]/', '', $value);
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);

            return is_numeric($normalized) ? (float) $normalized : null;
        });
    }

    /**
     *
     * @param array $transactions
     * @param int $lastMonths
     * @return array
     */
    private function filterTransactionsByLastMonths(array $transactions, int $lastMonths): array
    {
        if ($lastMonths === 0) {
            return $transactions;
        }

        $startDate = Carbon::now()->startOfMonth()->subMonths($lastMonths);

        return array_filter($transactions, function ($transaction) use ($startDate) {
            $transactionDate = Carbon::parse($transaction->getTransactionDate());
            return $transactionDate->greaterThanOrEqualTo($startDate);
        });
    }
}
