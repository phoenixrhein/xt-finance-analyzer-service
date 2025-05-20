<?php

namespace de\xovatec\financeAnalyzer\Dto\FinQuery;

use ArrayIterator;
use de\xovatec\financeAnalyzer\Enums\LogicalOperator;
use IteratorAggregate;

class ConditionList implements IteratorAggregate
{
    /**
     *
     * @param LogicalOperator|null $logicalOperator
     */
    public function __construct(private ?LogicalOperator $logicalOperator = null)
    {
    }

    /**
     *
     * @var Condition[]
     */
    private array $conditions = [];

    /**
     * Add a condition instance to the collection.
     *
     * @param Condition $condition
     * @return ConditionList
     */
    public function add(Condition|ConditionList $condition): ConditionList
    {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Add multiple condition instances to the collection.
     *
     * @param Condition[] $conditions
     * @return ConditionList
     */
    public function addMany(array $conditions): ConditionList
    {
        foreach ($conditions as $condition) {
            $this->add($condition);
        }

        return $this;
    }

    /**
     *
     * @param bool $onlyRaw
     * @return LogicalOperator|null
     */
    public function getLogicalOperator(bool $onlyRaw = false): ?LogicalOperator
    {
        if ($onlyRaw) {
            return $this->logicalOperator;
        }
        return $this->logicalOperator ?? LogicalOperator::AND;
    }

    /**
     *
     * @param LogicalOperator $logicalOperator
     * @return ConditionList
     */
    public function setLogicalOperator(LogicalOperator $logicalOperator): ConditionList
    {
        $this->logicalOperator = $logicalOperator;
        return $this;
    }

    /**
     * Get all conditions in the collection.
     *
     * @return Condition[]
     */
    public function all(): array
    {
        return $this->conditions;
    }

    /**
     * Get the number of conditions in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->conditions);
    }

    /**
     * Retrieve an external iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->conditions);
    }
}
