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
    public function __construct(private AccountListQuery $accountlistQuery, private SelectTargetPeriodAction $selectTargetPeriodAction)
    {
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
