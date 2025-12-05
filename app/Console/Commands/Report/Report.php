<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Report;

use de\xovatec\financeAnalyzer\Enums\TimespanType;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Services\Console\Actions\Report\SelectTargetPeriodAction;

use function Laravel\Prompts\select;

class Report extends FinCommand
{
    use BankAccountIdParameter;
    use SimpleInput;

    /**
     *
     * @param AccountListQuery $accountlistQuery
     */
    public function __construct(
        private AccountListQuery $accountlistQuery,
        private SelectTargetPeriodAction $selectTargetPeriodAction
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:report {accountId? : [:cli.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.report.description';

    /**
     *
     * @return AccountListQuery|null
     */
    protected function getAccountListQuery(): ?AccountListQuery
    {
        return $this->accountlistQuery;
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $account = ($this->getBankAccount(
            $this->argument('accountId') ? (int) $this->argument('accountId') : null,
            true,
            true
        ));

        if (!$account instanceof BankAccount) {
            return;
        }

        $reportType = $this->selectReportType();
        $targetPeriod = $this->selectTargetPeriodAction->selectTargetPeriod($reportType, $account);
        $timeSpan = $this->selectTimeSpan($reportType);
    }

    /**
     *
     * @param TimespanType $reportType
     * @return integer
     */
    private function selectTimeSpan(TimespanType $reportType): int
    {
        $months = __('cli.report.select_time_span.span.months');
        $years = __('cli.report.select_time_span.span.years');
        return (int)select(
            label: __(
                'cli.report.select_time_span.label',
                ['span' => $reportType === TimespanType::month ? $months : $years]
            ),
            options: [
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
            ],
            default: '0',
            scroll: 5
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
