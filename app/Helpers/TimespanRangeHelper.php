<?php

namespace de\xovatec\financeAnalyzer\Helpers;

use Carbon\Carbon;
use de\xovatec\financeAnalyzer\Enums\TimespanType;
use de\xovatec\financeAnalyzer\Helpers\DateRangeHelper;

class TimespanRangeHelper
{
    /**
     *
     * @param string $to
     * @param integer $span
     * @param TimespanType $type
     * @return array
     */
    public static function calculateRange(string $to, int $span, TimespanType $type): array
    {
        $length = strlen($to);
        $ranges = DateRangeHelper::parseDateRange($to);
        $to = $ranges[DateRangeHelper::TO];
        $to = Carbon::createFromFormat('Y-m-d', $to);

        $ranges = [];

        for ($i = 1; $i <= $span; $i++) {
            $from = clone $to;
            if ($type == TimespanType::year) {
                $from = $from->startOfYear();
            } elseif ($type == TimespanType::month) {
                $from = $from->startOfMonth();
            }

            $ranges[] = [
                DateRangeHelper::FROM => $from->format('Y-m-d'),
                DateRangeHelper::TO => $to->format('Y-m-d')
            ];

            if ($length == 8) {
                if ($type == TimespanType::year) {
                    $to = $to->subYear();
                } elseif ($type == TimespanType::month) {
                    $to = $to->subMonth();
                }
            } else {
                $to = $from->subDay();
            }
        }

        return $ranges;
    }
}
