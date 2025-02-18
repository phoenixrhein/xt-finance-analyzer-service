<?php

namespace de\xovatec\financeAnalyzer\Helpers;

use de\xovatec\financeAnalyzer\Enums\TimespanType;

class TimespanRangeHelper
{
    /**
     *
     * @param string $to
     * @param integer $span
     * @param TimespanType $type
     * @return void
     */
    public static function calculateRange(string $to, int $span, TimespanType $type)
    {
        $length = strlen($to);
        $ranges = DateRangeHelper::parseDateRange($to);
        $to = $ranges[DateRangeHelper::TO];

        $ranges = [];

        for ($i = 1; $i <= $span; $i++) {
            $from = clone $to;
            if ($type == TimespanType::year) {
                $from = $from->startOfYear();
            } elseif ($type == TimespanType::month) {
                $from = $from->startOfMonth();
            }

            $ranges[] = [$from->format('Y-m-d'), $to->format('Y-m-d')];

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
