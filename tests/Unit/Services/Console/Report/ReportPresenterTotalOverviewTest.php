<?php

namespace de\xovatec\Tests\financeAnalyzer\Unit\Services\Console\Report;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Dto\Report\PeriodReportData;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportPresenter;
use de\xovatec\Tests\financeAnalyzer\TestCase;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ReportPresenterTotalOverviewTest extends TestCase
{
    public function test_total_overview_sums_values_across_all_periods(): void
    {
        $output = $this->renderReport(new Collection([
            $this->period('2025-01-01', '2025-01-31', 1000.0, 400.0),
            $this->period('2025-02-01', '2025-02-28', 2000.0, 600.0),
            $this->period('2025-03-01', '2025-03-31', 1500.0, 800.0),
        ]));

        $this->assertStringContainsString('GESAMTÜBERSICHT', $output);
        $this->assertStringContainsString('Gesamteinnahmen', $output);
        $this->assertStringContainsString('Gesamtausgaben', $output);
        $this->assertStringContainsString('Gesamtsaldo', $output);
        $this->assertSame(1, substr_count($output, '4.500,00 €'));
        $this->assertSame(1, substr_count($output, '1.800,00 €'));
        $this->assertSame(1, substr_count($output, '2.700,00 €'));

        $overview = substr($output, strpos($output, 'GESAMTÜBERSICHT'));
        $this->assertStringContainsString('GESAMT', $overview);
        $this->assertStringNotContainsString('Oktober', $overview);
        $this->assertStringNotContainsString('September', $overview);
        $this->assertStringNotContainsString('August', $overview);
        $this->assertStringContainsString("\n\nGESAMTÜBERSICHT", $output);
    }

    public function test_total_overview_matches_the_values_of_a_single_period(): void
    {
        $output = $this->renderReport(new Collection([
            $this->period('2025-01-01', '2025-01-31', 1000.0, 400.0),
        ]));

        $this->assertStringContainsString('Gesamteinnahmen', $output);
        $this->assertStringContainsString('1.000,00 €', $output);
        $this->assertStringContainsString('400,00 €', $output);
        $this->assertStringContainsString('600,00 €', $output);
    }

    public function test_empty_report_does_not_render_total_overview(): void
    {
        $output = $this->renderReport(new Collection);

        $this->assertStringContainsString('Keine Daten für den gewählten Zeitraum vorhanden.', $output);
        $this->assertStringNotContainsString('GESAMTÜBERSICHT', $output);
    }

    private function renderReport(Collection $reportData): string
    {
        app()->setLocale('de');
        $output = new BufferedOutput;
        app()->instance(OutputInterface::class, $output);

        (new ReportPresenter)->render($reportData);

        return $output->fetch();
    }

    private function period(string $start, string $end, float $income, float $outgoing): PeriodReportData
    {
        return new PeriodReportData(
            Carbon::parse($start),
            Carbon::parse($end),
            new Collection,
            new Collection,
            $income,
            $outgoing,
            $income - $outgoing,
            new Collection
        );
    }
}
