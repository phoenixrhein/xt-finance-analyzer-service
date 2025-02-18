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
     * @param string $type
     * @param string $hint
     * @return integer|float|string
     */
    protected function viewInput(
        string $label,
        string|array $rules,
        mixed $rawInput = null,
        string $type = self::VALUE_TYPE_TEXT,
        string $hint = null,
    ): int|float|string {
        $input = $rawInput;
        do {
            $input = text(
                label: $label,
                default: ($type != self::VALUE_TYPE_DECIMAL ? $input : Str::replace('.', ',', $input)) ?? '',
                hint: $hint ?? ''
            );

            $valid = $this->viewValidatorError(
                [
                    __('cli.base.value') => $type != self::VALUE_TYPE_DECIMAL ? $input : Str::replace(',', '.', $input)
                ],
                [
                    __('cli.base.value') => $rules
                ]
            );
        } while (!$valid);

        return $type != self::VALUE_TYPE_DECIMAL ? $input : Str::replace(',', '.', $input);
    }
}
