<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Transaction;

use League\Csv\Reader;
use Illuminate\Console\Command;
use de\xovatec\financeAnalyzer\Services\ImportTransactionService;
use Exception;

class TransactionImport extends Command
{
    /**
     *
     * @param ImportTransactionService $importTransactionService
     */
    public function __construct(private ImportTransactionService $importTransactionService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:import {file : The file path to the CSV file} {--ignoreAlreadyExists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import SPK camt52v8 csv transaction file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        // Check if the parameter was passed
        if (!$filePath) {
            $this->error('No file path was provided.');
            exit();
        }

        // Check if the file exists
        if (!file_exists($filePath)) {
            $this->error('The specified file does not exist.');
            exit();
        }

        // Check if it is a CSV file
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (strtolower($fileExtension) !== 'csv') {
            $this->error('The specified file is not a CSV file.');
            exit();
        }

        $transactionFile = Reader::createFromPath($filePath, 'r');

        try {
            $this->importTransactionService->setIgnoreAlreadyExists($this->option('ignoreAlreadyExists'));
            $this->importTransactionService->import($transactionFile);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        } finally {
            $this->info(
                "Imported {$this->importTransactionService->getNumberOfImported()} rows /" .
                " duplicates {$this->importTransactionService->getNumberOfDuplicates()} row"
            );
        }
    }
}
