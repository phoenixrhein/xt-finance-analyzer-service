<?php

namespace de\xovatec\financeAnalyzer\Services\Import;

use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use de\xovatec\financeAnalyzer\Dto\Import\Report;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use Illuminate\Database\UniqueConstraintViolationException;
use de\xovatec\financeAnalyzer\Dto\Import\FileRecord\TransactionRecord;
use de\xovatec\financeAnalyzer\Exceptions\FinErrorException;
use de\xovatec\financeAnalyzer\Services\Import\Progress\ProgressDisplayInterface;
use de\xovatec\financeAnalyzer\Services\Import\Transaction\Detector\FileTypeDetector;
use de\xovatec\financeAnalyzer\Services\Import\Transaction\Format\ParserInterface;
use de\xovatec\financeAnalyzer\Services\Import\Transaction\Validator\FileValidatorService;

class ImportTransactionService
{
    /**
     *
     * @var Report
     */
    private Report $report;

    /**
     *
     * @param FileValidatorService $fileValidator
     * @param FileTypeDetector $fileTypeDetector
     * @param ProgressDisplayInterface $progressDisplay
     */
    public function __construct(
        private FileValidatorService $fileValidator,
        private FileTypeDetector $fileTypeDetector,
        private ProgressDisplayInterface $progressDisplay
    ) {
        $this->report = new Report();
    }

    /**
     *
     * @param string $filePath
     * @param integer $lastMonths
     * @param boolean $ignoreAlreadyExists
     * @return int|null
     */
    public function import(string $filePath, int $lastMonths, bool $ignoreAlreadyExists): ?int
    {
        $this->fileValidator->validate($filePath);
        $records = $this->getParser($filePath)->parse($filePath, $lastMonths);
        $this->report = new Report();

        $accountId = null;

        foreach($records as $record) {
            /** @var TransactionRecord $record */
            if (!$record instanceof TransactionRecord) {
                throw new InvalidArgumentException('No supported transaction record: ' . get_class($record));
            }

            try {
                $account = BankAccount::where('iban', '=', $record->getBankAccountIban())->firstOrFail();

                if ($accountId !== null && $accountId !== $account->id) {
                    throw new FinErrorException(
                        'Different bank accounts in import file. Expected: ' . $accountId . ' / Get: ' . $account->id,
                        'msgctx.transaction.import.error.different_accounts',
                        ['expected' => $accountId, 'get' => $account->id]
                    );
                }
                $accountId = $account->id;

                $this->insertRow($record);
                $this->progressDisplay->show();
                $this->report->incrementImported();
            } catch (UniqueConstraintViolationException $e) {
                $errorText = 'Duplicate import row: ' . implode('|', $record->getRecord());
                Log::error($errorText);
                if ($ignoreAlreadyExists === false) {
                    throw $e;
                }
            }
        }
        $this->progressDisplay->reset();

        return $accountId;
    }

    /**
     *
     * @param TransactionRecord $record
     * @return void
     */
    private function insertRow(TransactionRecord $record): void
    {
        Transactions::create([
            'bank_account_iban' => $record->getBankAccountIban(),
            'transaction_date' => $record->getTransactionDate(),
            'exchange_date' => $record->getExchangeDate(),
            'transaction_type' => $record->getTransactionType(),
            'reason_for_payment' => $record->getReasonForPayment(),
            'creditor_id' => $record->getCreditorId(),
            'mandate_ reference' => $record->getMandateReference(),
            'customer_reference' => $record->getCustomerReference(),
            'collector_reference' => $record->getCollectorReference(),
            'debit_original_amount' => $record->getDebitOriginalAmount(),
            'reimbursement_of_expenses_return_debit' => $record->getReimbursementOfExpensesReturnDebit(),
            'beneficiary_payee' => $record->getBeneficiaryPayee(),
            'creditor_iban' => $record->getCreditorIban(),
            'creditor_bic' => $record->getCreditorBic(),
            'amount' => $record->getAmount(),
            'currency' => $record->getCurrency(),
            'hash_identifier' => $record->getHashIdentifier()
        ]);
    }

    /**
     *
     * @param string $filePath
     * @return ParserInterface
     */
    private function getParser(string $filePath): ParserInterface
    {
        $formatDetector = $this->fileTypeDetector->detect($filePath);
        return $formatDetector->getParser($filePath);
    }

    /**
     *
     * @return Report
     */
    public function getReport(): Report
    {
        return $this->report;
    }
}
