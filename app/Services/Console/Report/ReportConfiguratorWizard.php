<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use de\xovatec\financeAnalyzer\Enums\TimespanType;
use de\xovatec\financeAnalyzer\Helpers\TimespanRangeHelper;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Console\Actions\Report\SelectTargetPeriodAction;

use function Laravel\Prompts\select;

class ReportConfiguratorWizard extends AbstractIOService
{
    /**
     *
     * @param SelectTargetPeriodAction $selectTargetPeriodAction
     */
    public function __construct(private SelectTargetPeriodAction $selectTargetPeriodAction)
    {
        parent::__construct();
    }

    /**
     *
     * @param BankAccount $bankAccount
     * @return array
     */
    public function runWizard(BankAccount $bankAccount): array
    {
        $reportType = $this->selectReportType();
        $targetPeriod = $this->selectTargetPeriodAction->selectTargetPeriod($reportType, $bankAccount);
        $timeSpan = $this->selectTimeSpan($reportType, $targetPeriod);

        return TimespanRangeHelper::calculateRange($targetPeriod, $timeSpan + 1, $reportType);
    }

    /**
     *
     * @param TimespanType $reportType
     * @param string $targetPeriod
     * @return int
     */
    private function selectTimeSpan(TimespanType $reportType, string $targetPeriod): int|string
    {
        $months = __('cli.report.select_time_span.span.months');
        $years = __('cli.report.select_time_span.span.years');
        $options = [
            '0' => __(
                'cli.report.select_time_span.time_span_options.0',
                [
                    'only_current' => $reportType === TimespanType::month
                        ? __('cli.report.select_time_span.only_current_month')
                        : __('cli.report.select_time_span.only_current_year')
                ]
            ),
            '1' => __(
                'cli.report.select_time_span.time_span_options.1',
                [
                    'span' => $reportType === TimespanType::month
                        ? __('cli.report.select_time_span.span.month')
                        : __('cli.report.select_time_span.span.year')
                ]
            ),
            '2' => __(
                'cli.report.select_time_span.time_span_options.2',
                ['span' => $reportType === TimespanType::month ? $months : $years]
            ),
            '3' => __(
                'cli.report.select_time_span.time_span_options.3',
                ['span' => $reportType === TimespanType::month ? $months : $years]
            ),
        ];

        if($reportType === TimespanType::month) {
            $month = (int)substr($targetPeriod, 4, 2);
            $monthUntilYearStart = $month - 1;
            $options[$monthUntilYearStart] = __('cli.report.select_time_span.time_span_options.until_year_start');
        }

        return (int)select(
            __(
                'cli.report.select_time_span.label',
                ['span' => $reportType === TimespanType::month ? $months : $years]
            ),
            $options,
            $options[3] ?? null,
            5
        );
    }

    /**
     *
     * @return TimespanType
     */
    private function selectReportType(): TimespanType
    {
        return TimespanType::fromName(select(
            __('cli.report.select_type'),
            [
                TimespanType::month->name => __('cli.report.type.monthly'),
                TimespanType::year->name => __('cli.report.type.yearly'),
            ]
        ));
    }
}

