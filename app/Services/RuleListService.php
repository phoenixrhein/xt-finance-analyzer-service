<?php

namespace de\xovatec\financeAnalyzer\Services;

use de\xovatec\financeAnalyzer\Models\Rule;
use de\xovatec\financeAnalyzer\Models\ConditionLink;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionBuilder;

class RuleListService
{
    /**
     * Holds the IDs of loaded condition links to avoid duplication
     *
     * @var array
     */
    private array $loadedConditionLinks = [];

    /**
     *
     * @param Rule $model
     * @param ExpressionBuilder $builder
     */
    public function __construct(private Rule $model, private ExpressionBuilder $builder)
    {
        
    }

    /**
     *
     * @return array
     */
    public function getRulesWithExpression(): array
    {
        $data = [];
        foreach ($this->getRules() as $rule) {
            $rule['expression'] = $this->builder->build($rule['condition_link']);
            $data[] = $rule;
        }

        return $data;
    }

    /**
     * Retrieves the rules along with their relationships.
     *
     * @return array
     */
    private function getRules(): array
    {
        $rules = $this->model->with(['actions.category'])->get();

        $rules->each(function ($rule) {
            $rule->loadMissing('conditionLink');
            $this->loadRecursiveConditionLink($rule->conditionLink);
        });

        return $rules->toArray();
    }

    /**
     * Loads the condition links recursively.
     *
     * @param ConditionLink|null $conditionLink
     * @return void
     */
    private function loadRecursiveConditionLink(?ConditionLink $conditionLink): void
    {
        if (!$conditionLink || in_array($conditionLink->id, $this->loadedConditionLinks)) {
            return;
        }

        $this->loadedConditionLinks[] = $conditionLink->id;

        if ($conditionLink->condition_type === 'condition') {
            $conditionLink->loadMissing('condition');
        } elseif ($conditionLink->condition_type === 'group') {
            $conditionLink->loadMissing('conditionGroup');
            if ($conditionLink->conditionGroup) {
                $this->loadRecursiveConditionLink($conditionLink->conditionGroup);
            }
        }

        $conditionLink->loadMissing('linkedCondition');
        if ($conditionLink->linkedCondition) {
            $this->loadRecursiveConditionLink($conditionLink->linkedCondition);
        }
    }
}
