<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Transaction;

use Illuminate\Support\Facades\Validator;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use de\xovatec\financeAnalyzer\Services\Import\ImportTransactionService;
use de\xovatec\financeAnalyzer\Services\Rule\RefreshTransactionRuleIndexService;

class TransactionImport extends FinCommand
{
    use SimpleInput;

    /**
     *
     * @param ImportTransactionService $importTransactionService
     */
    public function __construct(private ImportTransactionService $importTransactionService, private RefreshTransactionRuleIndexService $indexService)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:import {file : [:cli.transaction.import.param.file:]}' .
        ' {--lastMonths= : [:cli.transaction.import.param.lastMonths:]}' .
        ' {--ignoreAlreadyExists : [:cli.transaction.import.param.ignoreAlreadyExists:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction.import.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $lastMonths = $this->option('lastMonths');
        if (empty($lastMonths)) {
            $lastMonths = $this->viewInput(
                __('cli.transaction.import.input.lastmonths.text'),
                'required|numeric|min:0',
                1,
                self::VALUE_TYPE_TEXT,
                __('cli.transaction.import.input.lastmonths.hint')
            );
        } else {
            $validator = Validator::make(
                ['lastMonths' => $lastMonths],
                ['lastMonths' => 'required|numeric|min:0'],
            );

            if ($validator->fails()) {
                $this->emptyLn();
                $this->error($validator->errors()->first());
            }
        }

        $ignoreAlreadyExists = $this->option('ignoreAlreadyExists');
        if (empty($ignoreAlreadyExists)) {
            $ignoreAlreadyExists = $this->confirmPrompt(
                label: __('cli.transaction.import.input.ignoreAlreadyExists'),
                hint: __('cli.transaction.import.input.ignoreAlreadyExists_hint')
            );
        }

        try {
            $accountId = $this->importTransactionService->import(
                $this->argument('file'),
                $lastMonths,
                $ignoreAlreadyExists
            );
        } finally {
            $this->info(
                "Imported {$this->importTransactionService->getReport()->getImported()} rows /" .
                " duplicates {$this->importTransactionService->getReport()->getDuplicates()} row"
            );
        }

        $this->emptyLn();
        $this->info("Aktualisiere den Regel-Buchungs-Index...");
        $this->indexService->refreshAll(
            BankAccount::find($accountId),
            true
        );

        if ($accountId !== null) {
            $this->call(
                'fin:cash-detector',
                [
                    'accountId' => $accountId
                ]
            );
        }
    }
}
