<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery\Fields;

use Illuminate\Support\Facades\Validator;

abstract class BaseField
{
    /**
     * @var string
     */
    protected string $error = '';
    
    /**
     * constructor.
     *
     * @param string $column
     */
    public function __construct(protected string $column)
    {
    }

    /**
     *
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     *
     * @return string[]
     */
    abstract public function getOperators(): array;

    /**
     *
     * @param mixed $value
     * @return boolean
     */
    public function validate(mixed $value): bool
    {
        return $this->validateByRules($value, []);
    }

    /**
     *
     * @param mixed $value
     * @param array $rules
     * @return boolean
     */
    protected function validateByRules(mixed $value, array $rules): bool
    {
        if (empty($rules)) {
            return true;
        }

        $validator = Validator::make(
            [__('cli.base.value') => $value],
            [__('cli.base.value') => $rules]
        );

        $valid = true;
        if ($validator->fails()) {
            $valid = false;
            $this->error = $validator->errors()->first();
        }

        return $valid;
    }

    /**
     *
     * @return array|null
     */
    public function getSelectableValues(): ?array
    {
        return null;
    }

    /**
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }
}
