<?php

namespace de\xovatec\financeAnalyzer\Dto\FinQuery;

use ArrayIterator;
use IteratorAggregate;

class ConditionList implements IteratorAggregate
{
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
    public function add(Condition $condition): ConditionList
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
