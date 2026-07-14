<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Rule;

use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\UnmatchedTransactionsService;
use de\xovatec\financeAnalyzer\Traits\Command\View\Halt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

class UnmatchedTransactionsDisplayService extends AbstractIOService
{
    use Halt;

    /**
     * constructor
     *
     * @param UnmatchedTransactionsService $unmatchedTransactionsService
     */
    public function __construct(
        private readonly UnmatchedTransactionsService $unmatchedTransactionsService
    ) {
        parent::__construct();
    }
    
    /**
     *
     * @param Builder $unmatchedTransactions
     * @return void
     */
    public function displayUnmatchedTransactionsOverview(
        Builder $unmatchedTransactions
    ): void {
        if ($unmatchedTransactions->count() <= 0) {
            return;
        }

        $this->emptyLn();
        $this->alert(__('cli.rule.assign.count_unmatched_transactions', ['count' => $unmatchedTransactions->count()]));

        $topCounterpartyLimit = (int) config('report.display.rule_assign_top_counterparties_limit', 5);

        $topCounterparties = $this->unmatchedTransactionsService->getTopUnmatchedTransactionCounterparties(
            $unmatchedTransactions,
            $topCounterpartyLimit
        );

        
        if ($topCounterparties->isNotEmpty()) {
            $this->line(__('cli.rule.assign.top_counterparties.title', ['count' => $topCounterpartyLimit]));
            $this->displayTopCounterpartiesTable($topCounterparties);
        }

        $this->halt();
    }

    /**
     *
     * @param SupportCollection $topCounterparties
     * @return void
     */
    private function displayTopCounterpartiesTable(SupportCollection $topCounterparties): void
    {
        $headers = ['Zahlungsteilnehmer', 'IBAN', 'Anzahl'];
        $rows = [];

        foreach ($topCounterparties as $counterparty) {
            $displayName = $this->formatCounterpartyDisplayName(
                $counterparty->beneficiary_payee ?? null,
                $counterparty->creditor_iban ?? null
            );

            $rows[] = [
                $displayName,
                $counterparty->creditor_iban ?? '-',
                (int)$counterparty->transaction_count
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     *
     * @param string|null $beneficiaryPayee
     * @param string|null $creditorIban
     * @return string
     */
    private function formatCounterpartyDisplayName(?string $beneficiaryPayee, ?string $creditorIban): string
    {
        $displayName = preg_replace('/\\s+/', ' ', trim((string) $beneficiaryPayee)) ?? '';

        if ($displayName === '') {
            $displayName = preg_replace('/\\s+/', ' ', trim((string) $creditorIban)) ?? '';
        }

        if ($displayName === '') {
            return __('cli.rule.assign.top_counterparties.empty_iban');
        }

        return $displayName;
    }
}
