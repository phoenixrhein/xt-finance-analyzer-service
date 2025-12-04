<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Actions\Report;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Enums\TimespanType;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Helpers\DateRangeHelper;
use de\xovatec\financeAnalyzer\Helpers\TimespanRangeHelper;
use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;

class SelectTargetPeriodAction extends AbstractIOService
{
    use SimpleInput;

    /**
     *
     * @param TimespanType $reportType
     * @param BankAccount $bankAccount
     * @return string
     */
    public function selectTargetPeriod(TimespanType $reportType, BankAccount $bankAccount): string
    {
        if ($reportType === TimespanType::month) {
            $rules = ['required', 'regex:/^([0-9]{4})(0[1-9]|1[0-2])$/'];
        } elseif ($reportType === TimespanType::year) {
            $rules = ['required', 'regex:/^([0-9]{4})$/'];
        } else {
            throw new \InvalidArgumentException('Ungültiger Berichtstyp ausgewählt.');
        }

        $to = $this->selectTarget(
            $reportType,
            $bankAccount,
            __('cli.report.input_target.' . $reportType->name . '.label'),
            $rules,
            __('cli.report.input_target.' . $reportType->name . '.hint'),
        );
        return $to;
    }

    /**
     *
     * @param TimespanType $reportType
     * @param BankAccount $bankAccount
     * @param string $label
     * @param array $rules
     * @param string $hint
     * @return string
     */
    private function selectTarget(
        TimespanType $reportType,
        BankAccount $bankAccount,
        string $label,
        array $rules,
        string $hint
    ): string {
        $to = null;
        do {
            $to = $this->viewInput(
                label: $label,
                rules: $rules,
                hint: $hint,
                rawInput: $to
            );

            $isCompleted = $this->isCompletedTarget($this->calculateMaxTo($to, $reportType), $bankAccount);
            if (!$isCompleted) {
                $this->error(__('cli.report.input_target.error.incomplete_data'));
                $this->emptyLn();
            }
        } while (!$isCompleted);

        return $to;
    }

    /**
     *
     * @param string $to
     * @param TimespanType $reportType
     * @return Carbon
     */
    private function calculateMaxTo(string $to, TimespanType $reportType): Carbon
    {
        $ranges = TimespanRangeHelper::calculateRange($to, 1, $reportType);
        return Carbon::parse($ranges[0][DateRangeHelper::TO]);
    }

    /**
     *
     * @param Carbon $maxTo
     * @param BankAccount $bankAccount
     * @return boolean
     */
    private function isCompletedTarget(Carbon $maxTo, BankAccount $bankAccount): bool
    {
        $countOfLast = Transactions::whereBetween(
            'transaction_date',
            [
                $maxTo->clone()->subDays(5)->format('Y-m-d'),
                $maxTo->format('Y-m-d')
            ]
        )->where('bank_account_iban', $bankAccount->iban)->count();

        return $countOfLast > 0;
    }
}
