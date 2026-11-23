<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction;

use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\Equal;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\LessThan;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\NotEqual;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\GreaterThan;

class AmountField extends BaseField
{
    /**
     * AmountField constructor.
     */
    public function __construct()
    {
        parent::__construct('amount');
    }

    /**
     * @return string[]
     */
    public function getOperators(): array
    {
        return [
            Equal::class,
            NotEqual::class,
            GreaterThan::class,
            LessThan::class
        ];
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $this->error = __('validation.numeric', ['attribute' => __('cli.base.value')]);
            return false;
        }

        if (preg_match('/^-?\d+(?:[.,]\d{1,2})?$/', (string) $value) !== 1) {
            $this->error = __('validation.numeric', ['attribute' => __('cli.base.value')]);
            return false;
        }

        return true;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function normalizeValue(mixed $value): mixed
    {
        $value = (string) $value;

        if (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        if (str_contains($value, '.')) {
            $normalized = sprintf('%.2f', (float) $value);
            return (float) $normalized == 0.0 ? '0.00' : $normalized;
        }

        $negative = str_starts_with($value, '-');
        $digits = ltrim($negative ? substr($value, 1) : $value, '0');
        $digits = $digits === '' ? '0' : $digits;

        return $negative && $digits !== '0' ? '-' . $digits : $digits;
    }
}
