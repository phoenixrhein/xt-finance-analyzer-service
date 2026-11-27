<?php

namespace de\xovatec\Tests\financeAnalyzer\Unit\Services\Console\Report;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Dto\Report\PeriodReportData;
use de\xovatec\financeAnalyzer\Dto\Report\SavingsAccountReportData;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportPresenter;
use de\xovatec\Tests\financeAnalyzer\TestCase;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ReportPresenterSavingsTablesTest extends TestCase
{
    public function test_it_renders_separate_savings_tables_with_period_and_total_values(): void
    {
        $output = $this->renderReport(new Collection([
            $this->period('2025-01-01', '2025-01-31', [
                $this->account(1, 'Langfristig', '123', '2025-01-01', '2025-01-31', -100.0, 0.0),
                $this->account(2, 'Urlaub', '456', '2025-01-01', '2025-01-31', -30.0, 10.0),
                $this->account(3, 'Ohne Buchungen', '789', '2025-01-01', '2025-01-31', 0.0, 0.0),
            ]),
            $this->period('2025-02-01', '2025-02-28', [
                $this->account(1, 'Langfristig', '123', '2025-02-01', '2025-02-28', -45.0, 22.0),
                $this->account(2, 'Urlaub', '456', '2025-02-01', '2025-02-28', -20.0, 5.0),
                $this->account(3, 'Ohne Buchungen', '789', '2025-02-01', '2025-02-28', 0.0, 0.0),
            ]),
        ]));

        $this->assertSame(2, substr_count($output, 'SPARBUCH:'));
        $this->assertStringContainsString('SPARBUCH: Langfristig (...123)', $output);
        $this->assertStringContainsString('SPARBUCH: Urlaub (...456)', $output);
        $this->assertStringNotContainsString('Ohne Buchungen', $output);
        $this->assertStringNotContainsString('DE123456789', $output);
        $this->assertSame(2, substr_count($output, 'Summe'));

        $this->assertStringContainsString('145,00 €', $output);
        $this->assertStringContainsString('-22,00 €', $output);
        $this->assertStringContainsString('123,00 €', $output);
        $this->assertStringContainsString('50,00 €', $output);
        $this->assertStringContainsString('-15,00 €', $output);
        $this->assertStringContainsString('35,00 €', $output);
        $this->assertStringContainsString('│                -', $output);
    }

    private function renderReport(Collection $reportData): string
    {
        app()->setLocale('de');
        $output = new BufferedOutput;
        app()->instance(OutputInterface::class, $output);

        (new ReportPresenter)->render($reportData);

        return $output->fetch();
    }

    /**
     * @param  Collection<SavingsAccountReportData>  $savingsAccounts
     */
    private function period(string $start, string $end, array $savingsAccounts): PeriodReportData
    {
        return new PeriodReportData(
            Carbon::parse($start),
            Carbon::parse($end),
            new Collection,
            new Collection,
            0.0,
            0.0,
            0.0,
            new Collection($savingsAccounts)
        );
    }

    private function account(
        int $id,
        string $comment,
        string $ibanLastThree,
        string $start,
        string $end,
        float $toExcludedIban,
        float $fromExcludedIban
    ): SavingsAccountReportData {
        return new SavingsAccountReportData(
            $id,
            $comment,
            $ibanLastThree,
            Carbon::parse($start),
            Carbon::parse($end),
            $toExcludedIban,
            $fromExcludedIban
        );
    }
}
