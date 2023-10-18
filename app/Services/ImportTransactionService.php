<?php

namespace de\xovatec\financeAnalyzer\Services;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Exceptions\InvalidImportTransactionFileException;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use de\xovatec\financeAnalyzer\Models\Transactions;
use Illuminate\Database\UniqueConstraintViolationException;

class ImportTransactionService
{
    private const TRANSACTION_STATUS_PREPARED = 'Umsatz vorgemerkt';
    private const NUMBER_OF_COLUMNS = 17;

    /**
     *
     * @var integer
     */
    private int $imported = 0;

    /**
     *
     * @var integer
     */
    private int $duplicates = 0;

    /**
     *
     * @var boolean
     */
    private bool $ignoreAlreadyExists = false;

    /**
     *
     * @param Reader $transactionFile
     * @return void
     */
    public function import(Reader $transactionFile): void
    {
        $transactionFile->setDelimiter(';');
        // Read the header
        $header = $transactionFile->fetchOne();

        // Check if the number of columns in the header is correct
        if (count($header) !== self::NUMBER_OF_COLUMNS) {
            throw new InvalidImportTransactionFileException(
                'The CSV file does not contain the expected number of header columns: ' . count($header)
            );
        }

        $transactionFile->setHeaderOffset(0);
        $this->imported = 0;
        $this->duplicates = 0;

        foreach ($transactionFile->getRecords() as $row) {
            if (count($row) !== self::NUMBER_OF_COLUMNS) {
                throw new InvalidImportTransactionFileException(
                    'The CSV file contains an incorrect number of columns in one or more rows: ' . count($header)
                );
            }
            if ($row['Info'] === self::TRANSACTION_STATUS_PREPARED) {
                continue;
            }

            $importRow = implode('|', array_slice($row, 0, count($row) - 1));
            $hashIdentifier = hash('sha256', $importRow);

            try {
                $this->insertRow($row, $hashIdentifier);
                $this->imported++;
            } catch (UniqueConstraintViolationException $e) {
                $this->duplicates++;
                $errorText = 'Duplicate import row: ' . $importRow;
                if (app()->runningInConsole()) {
                    echo $errorText . PHP_EOL;
                } else {
                    app()->make(LoggerInterface::class)->error($errorText);
                }
                if ($this->ignoreAlreadyExists === false) {
                    throw $e;
                }
            }
        }
    }

    /**
     *
     * @param array $row
     * @param string $hashIdentifier
     * @return void
     */
    private function insertRow(array $row, string $hashIdentifier): void
    {
        BankAccount::where('iban', '=', $row['Auftragskonto'])->firstOrFail();
        Transactions::create([
            'bank_account_iban' => $row['Auftragskonto'],
            'booking_date' => Carbon::createFromFormat('d.m.y', $row['Buchungstag'])->format('Y-m-d'),
            'exchange_date' => Carbon::createFromFormat('d.m.y', $row['Valutadatum'])->format('Y-m-d'),
            'booking_type' => $row['Buchungstext'],
            'reason_for_payment' => utf8_encode($row['Verwendungszweck']),
            'creditor_id' => $row['Glaeubiger ID'],
            'mandate_ reference' => $row['Mandatsreferenz'],
            'customer_reference' => $row['Kundenreferenz (End-to-End)'],
            'collector_reference' => $row['Sammlerreferenz'],
            'debit_original_amount' => strlen($row['Lastschrift Ursprungsbetrag'])
                ? strlen($row['Lastschrift Ursprungsbetrag'])
                : null,
            'reimbursement_of_expenses_return_debit' => $row['Auslagenersatz Ruecklastschrift'],
            'beneficiary_payee' => $row['Beguenstigter/Zahlungspflichtiger'],
            'creditor_iban' => $row['Kontonummer/IBAN'],
            'creditor_bic' => $row['BIC (SWIFT-Code)'],
            'amount' => str_replace(',', '.', str_replace('.', '', $row['Betrag'])),
            'currency' => $row['Waehrung'],
            'hash_identifier' => $hashIdentifier
        ]);
    }

    /**
     *
     * @param boolean $ignoreAlreadyExists
     * @return void
     */
    public function setIgnoreAlreadyExists(bool $ignoreAlreadyExists): void
    {
        $this->ignoreAlreadyExists = $ignoreAlreadyExists;
    }

    /**
     *
     * @return integer
     */
    public function getNumberOfImported(): int
    {
        return $this->imported;
    }

    /**
     *
     * @return integer
     */
    public function getNumberOfDuplicates(): int
    {
        return $this->duplicates;
    }
}
