<?php

namespace de\xovatec\financeAnalyzer\Traits\Command;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Helpers\DateRangeHelper;
use de\xovatec\financeAnalyzer\Traits\Command\View\BaseView;

trait DateRangeParameter
{
    use BaseView;

    /**
     *
     * @param string $rangeStr
     * @return array|null
     */
    public function prepareRangeParam(string $rangeStr): array|null
    {
        $this->emptyLn();
        if (!DateRangeHelper::validFormat($rangeStr)) {
            $this->error(__('cli.param.date_range.error.time_period_invalid'));
            return null;
        }
        $range = DateRangeHelper::parseDateRange($rangeStr);
        if (!DateRangeHelper::validFromTo($range[DateRangeHelper::FROM], $range[DateRangeHelper::TO])) {
            $this->error(__('cli.param.date_range.error.not_start_before_end'));
            return null;
        }

        $from = Carbon::parse($range[DateRangeHelper::FROM])->format('d.m.Y');
        $to = Carbon::parse($range[DateRangeHelper::TO])->format('d.m.Y');
        $this->info(__('cli.param.date_range.duration') . ': ' . $from . ' - ' . $to);

        return $range;
    }
}
