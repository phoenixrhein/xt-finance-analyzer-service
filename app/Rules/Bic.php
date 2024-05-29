<?php

namespace de\xovatec\financeAnalyzer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Bic implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValid($value)) {
            $fail(__('validation.bic'));
        }
    }

    /**
     *
     * @param string $value
     * @return boolean
     */
    public function isValid(string $value): bool
    {
        return (bool) preg_match("/^[A-Za-z]{4} ?[A-Za-z]{2} ?[A-Za-z0-9]{2} ?([A-Za-z0-9]{3})?$/", $value);
    }
}
