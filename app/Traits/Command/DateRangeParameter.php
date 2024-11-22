<?php

namespace de\xovatec\financeAnalyzer\Traits\Command;

use de\xovatec\financeAnalyzer\Helpers\DateRangeHelper;
use de\xovatec\financeAnalyzer\Traits\Command\View\BaseView;

trait DateRangeParameter
{
    use BaseView;
    
    private function prepareRangeParam(string $rangeStr): array|null
    {
        if (!DateRangeHelper::validFormat($rangeStr)) {
            $this->emptyLn();
            $this->error(__('cli.param.date_range.error.time_period_invalid'));
            return null;
        }
        $range = DateRangeHelper::parseDateRange($rangeStr);
        if (!DateRangeHelper::validFromTo($range[DateRangeHelper::FROM], $range[DateRangeHelper::TO])) {
            $this->emptyLn();
            $this->error(__('cli.param.date_range.error.not_start_before_end'));
            return null;
        }
        
        return $range;
    }
}
