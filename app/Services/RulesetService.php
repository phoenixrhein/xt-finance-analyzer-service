<?php

namespace de\xovatec\financeAnalyzer\Services;

use de\xovatec\financeAnalyzer\Models\Ruleset;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionBuilder;

class RulesetService
{
    public function __construct(private Ruleset $model, private ExpressionBuilder $builder)
    {
        
    }

    /**
     *
     * @return array
     */
    public function getMainRulesetsWithExpression(): array
    {
        $data = [];
        foreach($this->getMainRuleset() as $mainRuleset) {
            $mainRuleset['expression'] = $this->builder->build($mainRuleset['condition']);
            $data[] = $mainRuleset;
        }

        return $data;
    }

    /**
     *
     * @return array
     */
    private function getMainRuleset(): array
    {
        $getLinkedCondtion = function($query) use(&$getLinkedCondtion) {
            $row = $query->get()->first();
            if ($row == null) {
                return null;
            }
            $row = $row->toArray();
            if ($row['condition_type'] == 'rule') {
                return $query->with('rule');
            }
            return $query->with([
                'ruleset',
                'ruleset.condition',
                'ruleset.condition.rule',
                'ruleset.condition.ruleset',
                'ruleset.condition.linkedCondition' => function($query) use($getLinkedCondtion) {
                    return $getLinkedCondtion($query);
                }
            ]);
        };
        
        return $this->model->with([
            'actions.category',
            'condition',
            'condition.rule',
            'condition.ruleset',
            'condition.linkedCondition' => function($query) use($getLinkedCondtion) {
                return $getLinkedCondtion($query);
            }])->where('type', 'main')->get()->toArray();
    }
}
