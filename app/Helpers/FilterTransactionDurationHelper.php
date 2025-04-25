<?php

namespace de\xovatec\financeAnalyzer\Helpers;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use Illuminate\Database\Eloquent\Builder;

class FilterTransactionDurationHelper
{
    /**
     *
     * @param Builder $transactions
     * @param string $rangeStr
     * @param FinCommand $command
     * @return Builder
     */
    public static function applyFilter(Builder $transactions, string $rangeStr, FinCommand $command): Builder
    {
        $range = $command->prepareRangeParam($rangeStr);
        if ($range === null) {
            return $transactions;
        }

        $from = $range[DateRangeHelper::FROM];
        $to = $range[DateRangeHelper::TO];

        $transactions = $transactions->where('transaction_date', '>=', $from)
            ->where('transaction_date', '<=', $to);

        return $transactions;
    }
}
