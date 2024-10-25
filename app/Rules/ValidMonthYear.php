<?php

namespace de\xovatec\financeAnalyzer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMonthYear implements ValidationRule
{
    /**
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) !== 6) {
            $fail(__('validation.month_year.invalid_length'));
            return;
        }

        $month = substr($value, 0, 2);
        $year = substr($value, 2, 4);

        if (!is_numeric($month) || !is_numeric($year)) {
            $fail(__('validation.month_year.not_numeric'));
            return;
        }

        if ((int)$month < 1 || (int)$month > 12) {
            $fail(__('validation.month_year.no_month'));
        }
    }
}
