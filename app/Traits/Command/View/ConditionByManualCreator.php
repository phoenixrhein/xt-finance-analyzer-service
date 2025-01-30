<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Services\FinQuery\FieldConfig;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\BaseOperator;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Services\FinQuery\LogicalOperator;
use Illuminate\Database\Eloquent\Collection;

use function Laravel\Prompts\select;

trait ConditionByManualCreator
{
    use SimpleInput;
    use TableConsolePagination;

    /**
     * @var int
     */
    private const DISPLAY_LIMIT = 5;

    /**
     * @return FinQueryBuilder
     */
    abstract private function getFinQueryBuilder(): FinQueryBuilder;

    /**
     * @return SqlQueryBuilder
     */
    abstract private function getSqlQueryBuilder(): SqlQueryBuilder;

    /**
     * @param BankAccount $bankAccount
     * @param Collection|null $ignoreIbans
     * @return ConditionList
     */
    private function viewConditionByManualCreator(
        BankAccount $bankAccount,
        ?Collection $ignoreIbans = null
    ): ConditionList {
        $conditions = new ConditionList();
        $condition = null;
        $logicalOperator = '';
        do {
            $condition = $this->inputCondition($condition, $logicalOperator);

            $this->displayFinQuery($conditions, $condition);
            $this->displayInterimResult($bankAccount, $conditions, $condition, $ignoreIbans);

            if (!$this->confirmPrompt(__('cli.view.condition_creator.confirm_condition'))) {
                continue;
            }

            $logicalOperator = select(
                __('cli.view.condition_creator.further_condition'),
                [
                    LogicalOperator::AND => __('cli.view.condition_creator.option_and_link'),
                    LogicalOperator::OR => __('cli.view.condition_creator.option_or_link'),
                    LogicalOperator::NONE => __('cli.view.condition_creator.option_no_more_condition')
                ]
            );
            $conditions->add($condition);

            $condition = null;
        } while ($logicalOperator !== LogicalOperator::NONE);

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
     * @param BankAccount $bankAccount
     * @param ConditionList $conditions
     * @param Condition $condition
     * @param Collection|null $ignoreIbans
     * @return void
     */
    private function displayInterimResult(
        BankAccount $bankAccount,
        ConditionList $conditions,
        Condition $condition,
        ?Collection $ignoreIbans
    ): void {
        $query = Transactions::where('bank_account_iban', $bankAccount->iban);

        if ($ignoreIbans instanceof Collection && $ignoreIbans->isNotEmpty()) {
            $query->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        }

        $this->getSqlQueryBuilder()->build(
            $query,
            (new ConditionList())->addMany($conditions->all())->add($condition)
        );

        $query->select(array_keys(TransactionList::$compactView))
            ->limit(self::DISPLAY_LIMIT);

        $this->tableConsolePagination(
            $query->get(),
            TransactionList::$compactView,
            null,
            'cli.transaction.base.table.header.'
        );

        if ($query->count() > self::DISPLAY_LIMIT) {
            $this->line(
                ($query->count() - self::DISPLAY_LIMIT) . ' ' . __('cli.view.condition_creator.more_matches_found')
            );
        }
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
                    fn($operator) => __('cli.base.operator.' . (new $operator())->getId()) .
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
