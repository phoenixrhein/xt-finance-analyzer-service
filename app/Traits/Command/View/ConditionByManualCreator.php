<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Database\Eloquent\Builder;
use de\xovatec\financeAnalyzer\Enums\LogicalOperator;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use de\xovatec\financeAnalyzer\Services\FinQuery\FieldConfig;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\BaseOperator;
use de\xovatec\financeAnalyzer\Traits\Command\DisplayInterimTransactionResult;

use function Laravel\Prompts\select;

trait ConditionByManualCreator
{
    use SimpleInput;
    use DisplayInterimTransactionResult;

    /**
     * @var string
     */
    private const LOGICAL_OPERATOR_NONE = 'none';

    /**
     * @return FinQueryBuilder
     */
    abstract private function getFinQueryBuilder(): FinQueryBuilder;

    /**
     * @param Builder $transactions
     * @return ConditionList
     */
    private function viewConditionByManualCreator(Builder $transactions): ConditionList
    {
        $conditions = new ConditionList();
        $condition = null;
        $logicalOperator = '';
        do {
            $condition = $this->inputCondition($condition, $logicalOperator);

            $this->displayFinQuery($conditions, $condition);
            $this->displayInterimResult(
                CopyBuilderQueryHelper::copy($transactions),
                (new ConditionList())->addMany($conditions->all())->add($condition)
            );

            if (!$this->confirmPrompt(__('cli.view.condition_creator.confirm_condition'))) {
                continue;
            }

            $logicalOperator = select(
                __('cli.view.condition_creator.further_condition'),
                [
                    LogicalOperator::AND->value => __('cli.view.condition_creator.option_and_link'),
                    LogicalOperator::OR->value => __('cli.view.condition_creator.option_or_link'),
                    self::LOGICAL_OPERATOR_NONE => __('cli.view.condition_creator.option_no_more_condition')
                ]
            );
            $conditions->add($condition);

            $condition = null;
        } while ($logicalOperator !== self::LOGICAL_OPERATOR_NONE);

        return $conditions;
    }

    /**
     * @param ConditionList $conditions
     * @param Condition $condition
     * @return void
     */
    private function displayFinQuery(ConditionList $conditions, Condition $condition): void
    {
        $newfinQuery = $this->getFinQueryBuilder()->build((new ConditionList())->add($condition));
        $finQuery = $this->getFinQueryBuilder()->build($conditions);
        $this->line('<bg=cyan>FinQuery:</> ' . $finQuery . '<fg=yellow;options=bold> ' . $newfinQuery . '</>');
    }

    /**
     *
     * @param Condition|null $condition
     * @param string $logicalOperator
     * @return Condition
     */
    private function inputCondition(?Condition $condition, string $logicalOperator): Condition
    {
        $field = $this->selectField($condition ? $condition->getField() : null);
        $operator = $this->selectOperator($field, $condition ? $condition->getOperator() : null);
        $value = $this->getValue($field, $condition ? $condition->getValue() : null);

        return new Condition($field, $operator, $value, $logicalOperator);
    }

    /**
     * @param BaseField|null $default
     * @return BaseField
     */
    private function selectField(BaseField $default = null): ?BaseField
    {
        $fields = FieldConfig::getAvailableFields();
        $fieldNames = array_map(fn(BaseField $field) => $field->getColumn(), $fields);
        $options = [];
        foreach ($fieldNames as $fieldName) {
            $options[$fieldName] = __('cli.transaction.base.table.header.' . $fieldName);
        }
        $selectedFieldName = select(
            __('cli.view.condition_creator.select_field'),
            $options,
            $default instanceof BaseField ? $default->getColumn() : null
        );

        return collect($fields)->first(fn(BaseField $field) => $field->getColumn() === $selectedFieldName);
    }

    /**
     * @param BaseField $field
     * @param BaseOperator|null $default
     * @return BaseOperator
     */
    private function selectOperator(BaseField $field, BaseOperator $default = null): BaseOperator
    {
        $operatorId = select(
            __('cli.view.condition_creator.select_operator'),
            array_combine(
                array_map(fn($operator) => (new $operator())->getId(), $field->getOperators()),
                array_map(
                    fn($operator) => __('cli.view.condition_creator.operator.' . (new $operator())->getId()) .
                        ' (' . (new $operator())->getFinQueryOperator() . ')',
                    $field->getOperators()
                )
            ),
            $default instanceof BaseOperator ? $default->getId() : null
        );

        $className = collect($field->getOperators())
            ->first(fn($operator) => (new $operator())->getId() === $operatorId);
        return new $className();
    }

    /**
     *
     * @param BaseField $field
     * @param mixed $default
     * @return mixed
     */
    private function getValue(BaseField $field, mixed $default = null): mixed
    {
        if ($field->getSelectableValues()) {
            return select(__('cli.view.condition_creator.value_select'), $field->getSelectableValues(), $default);
        }

        return $this->promptInput(__('cli.view.condition_creator.value_input'), $field, $default);
    }

    /**
     * @param string $message
     * @param BaseField $field
     * @param mixed $default
     * @return mixed
     */
    private function promptInput(string $message, BaseField $field, mixed $default = null): mixed
    {
        do {
            $valid = true;
            $value = $this->viewInput($message, 'required', $default ?? '');

            if (!$field->validate($value)) {
                $this->error($field->getError());
                $valid = false;
            }
        } while (!$valid);

        return $value;
    }
}
