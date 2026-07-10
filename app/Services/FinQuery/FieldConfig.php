<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery;

use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\NoteField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\AmountField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\CreditorIbanField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\TransactionTypeField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\BeneficiaryPayeeField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\ReasonForPaymentField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\IdField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\BaseOperator;
use InvalidArgumentException;

class FieldConfig
{
    /**
     *
     * @return BaseField[]
     */
    public static function getAvailableFields(): array
    {
        return [
            new AmountField(),
            new BeneficiaryPayeeField(),
            new CreditorIbanField(),
            new IdField(),
            new NoteField(),
            new ReasonForPaymentField(),
            new TransactionTypeField(),
        ];
    }

    /**
     *
     * @return BaseField[]
     */
    public static function getAvailableFieldsWithColumnKey(): array
    {
        $fields = [];
        foreach (self::getAvailableFields() as $field) {
            $fields[$field->getColumn()] = $field;
        }
        return $fields;
    }

    /**
     *
     * @param string $columnColumn
     * @return BaseField
     */
    public static function getFieldByColumnKey(string $columnColumn): BaseField
    {
        foreach (self::getAvailableFields() as $field) {
            if ($field->getColumn() === $columnColumn) {
                return $field;
            }
        }
        throw new InvalidArgumentException('Field not found: ' . $columnColumn);
    }

    /**
     *
     * @param BaseField $field
     * @return array
     */
    public static function getAvailableFieldOperator(BaseField $field): array
    {
        $operators = [];
        foreach ($field->getOperators() as $operatorClass) {
            $operators[] = (new $operatorClass())->getFinQueryOperator();
        }

        return $operators;
    }

    /**
     *
     * @param BaseField $field
     * @param string $operator
     * @return BaseOperator
     */
    public static function getOperatorClass(BaseField $field, string $operator): BaseOperator
    {
        foreach ($field->getOperators() as $operatorClass) {
            if ((new $operatorClass())->getFinQueryOperator() === $operator) {
                return new $operatorClass();
            }
        }

        throw new InvalidArgumentException('Operator not found: ' . $operator);
    }
}
