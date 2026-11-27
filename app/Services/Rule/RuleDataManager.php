<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use de\xovatec\financeAnalyzer\Models\Rule;
use de\xovatec\financeAnalyzer\Models\Action;
use de\xovatec\financeAnalyzer\Models\Category;
use de\xovatec\financeAnalyzer\Models\Condition;
use de\xovatec\financeAnalyzer\Enums\ConditionType;
use de\xovatec\financeAnalyzer\Models\ConditionLink;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Exceptions\ExpressionSyntaxException;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Enums\RuleTargetType;
use de\xovatec\financeAnalyzer\Enums\Cashflow;
use de\xovatec\financeAnalyzer\Services\Rule\RefreshTransactionRuleIndexService;

class RuleDataManager
{
    /**
     * @param RuleToConditionTransformer $transformer
     * @param RefreshTransactionRuleIndexService $indexService
     */
    public function __construct(
        private RuleToConditionTransformer $transformer,
        private RefreshTransactionRuleIndexService $indexService
    ) {
    }

    /**
     *
     * @param array $conditionData
     * @return integer
     */
    private function saveCondition(array $conditionData): int
    {
        if ($conditionData['conditionType'] === ConditionType::condition) {
            $condition = Condition::create([
                'field_identifier' => $conditionData['condition']['field'],
                'compare_operator' => $conditionData['condition']['comparer'],
                'value' => $conditionData['condition']['value']
            ]);
            $id = $condition->id;
        } elseif ($conditionData['conditionType'] === ConditionType::group) {
            $id = $this->saveCondition($conditionData['condition']);
        } else {
            throw new ExpressionSyntaxException('No valid condition type given: ' . $conditionData['conditionType']);
        }

        $conditionLinkId = null;
        if (is_array($conditionData['linkTo'])) {
            $conditionLinkId = $this->saveCondition($conditionData['linkTo']);
        }

        $conditionLink = ConditionLink::create([
            'foreign_id' => $id,
            'condition_type' => $conditionData['conditionType']->name,
            'condition_link_id' => $conditionLinkId,
            'link_operator' => $conditionData['logicOperator']
        ]);
        return $conditionLink->id;
    }

    /**
     *
     * @param string $name
     * @param integer $categoryId
     * @param ConditionList $conditionList
     * @param integer $bankAccountId
     * @throws ExpressionSyntaxException
     * @return int
     */
    public function saveRule(string $name, int $categoryId, ConditionList $conditionList, int $bankAccountId): int
    {
        $category = Category::findOrFail($categoryId);
        $cashflowType = $category->getCashflowType();

        return $this->saveRuleForTarget(
            $name,
            $cashflowType === Cashflow::in ? $categoryId : null,
            $cashflowType === Cashflow::out ? $categoryId : null,
            $conditionList,
            $bankAccountId,
            RuleTargetType::TRANSACTION
        );
    }

    /**
     *
     * @param string $name
     * @param integer|null $incomeCategoryId
     * @param integer|null $outcomeCategoryId
     * @param ConditionList $conditionList
     * @param integer $bankAccountId
     * @param RuleTargetType $targetType
     * @return int
     */
    public function saveRuleForTarget(
        string $name,
        ?int $incomeCategoryId,
        ?int $outcomeCategoryId,
        ConditionList $conditionList,
        int $bankAccountId,
        RuleTargetType $targetType,
        ?array $targetIds = null
    ): int {
        if ($incomeCategoryId === null && $outcomeCategoryId === null) {
            throw new \InvalidArgumentException('At least one category must be provided.');
        }

        if (
            $targetType === RuleTargetType::TRANSACTION_SPLIT
            && $incomeCategoryId !== null
            && $outcomeCategoryId !== null
        ) {
            throw new \InvalidArgumentException('A transaction split rule can only have one category.');
        }

        if ($incomeCategoryId !== null) {
            Category::findOrFail($incomeCategoryId);
        }
        if ($outcomeCategoryId !== null) {
            Category::findOrFail($outcomeCategoryId);
        }

        $conditionLinkId = $this->saveCondition($this->transformer->transformToArray($conditionList));

        $rule = Rule::create([
            'name' => $name,
            'condition_link_id' => $conditionLinkId,
            'bank_account_id' => $bankAccountId,
            'target_type' => $targetType,
        ]);

        foreach (
            [
                Cashflow::in->name => $incomeCategoryId,
                Cashflow::out->name => $outcomeCategoryId,
            ] as $cashflowType => $categoryId
        ) {
            if ($categoryId !== null) {
                Action::create([
                    'rule_id' => $rule->id,
                    'category_id' => $categoryId,
                    'cashflow_type' => $cashflowType,
                ]);
            }
        }

        $bankAccount = BankAccount::find($bankAccountId);
        if ($targetType === RuleTargetType::TRANSACTION_SPLIT && $targetIds !== null) {
            foreach ($targetIds as $targetId) {
                \Illuminate\Support\Facades\DB::table('rule_transaction_split')->insert([
                    'rule_id' => $rule->id,
                    'transaction_split_id' => $targetId,
                    'bank_account_id' => $bankAccountId,
                ]);
            }
        } else {
            $this->indexService->addRule($rule->id, $bankAccount);
        }

        return $rule->id;
    }
}
