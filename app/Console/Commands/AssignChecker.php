<?php

namespace de\xovatec\financeAnalyzer\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class AssignChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:assign-checker {file : The file path to the CSV file}';

    protected $description = 'Checks the CSV file for validity and outputs the contents line by line.';

    public function handle()
    {
        $filePath = $this->argument('file');

        // Check if the parameter was passed
        if (!$filePath) {
            $this->error('No file path was provided.');
            return;
        }

        // Check if the file exists
        if (!file_exists($filePath)) {
            $this->error('The specified file does not exist.');
            return;
        }

        // Check if it is a CSV file
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (strtolower($fileExtension) !== 'csv') {
            $this->error('The specified file is not a CSV file.');
            return;
        }

        // Read the CSV file using league/csv
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setDelimiter(';');

        // Read the header
        $header = $csv->fetchOne();

        // Check if the number of columns in the header is correct
        if (count($header) !== 12) {
            $this->error('The CSV file does not contain the expected number of columns.');
            return;
        }

        // Iterate over each row and output it
        $records = $csv->getRecords();
        foreach ($records as $row) {
            // Check if the number of columns in the row is correct
            if (count($row) !== 12) {
                $this->error('The CSV file contains an incorrect number of columns in one or more rows.');
                return;
            }

            // Output the row to the console
            $this->comment (implode(' | ', $row));
        }

        $this->info('The CSV file was successfully checked.');
    }
}
