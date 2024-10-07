<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Support\Str;

use function Laravel\Prompts\text;

trait SimpleInput
{
    use BaseView;

    public const VALUE_TYPE_TEXT = 'text';
    public const VALUE_TYPE_DECIMAL = 'decimal';

    /**
     *
     * @param string $label
     * @param string|array $rules
     * @param mixed $rawInput
     * @param [type] $type
     * @return integer|float|string
     */
    protected function viewInput(
        string $label,
        string|array $rules,
        mixed $rawInput = null,
        string $type = self::VALUE_TYPE_TEXT
    ): int|float|string {
        $input = $rawInput;
        do {
            $input = text(
                label: $label,
                default: $input ?? ''
            );

            $valid = $this->viewValidatorError(
                [
                    'Wert' => $type != self::VALUE_TYPE_DECIMAL ? $input : Str::replace(',', '.', $input)
                ],
                [
                    'Wert' => $rules
                ]
            );

        } while (!$valid);

        return $type != self::VALUE_TYPE_DECIMAL ? $input : Str::replace(',', '.', $input);
    }
}
