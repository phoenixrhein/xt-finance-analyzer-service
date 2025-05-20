<?php

namespace de\xovatec\financeAnalyzer\Services\Rule;

use Illuminate\Support\Facades\DB;
use de\xovatec\financeAnalyzer\Models\Rule;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\Rule\RuleToConditionTransformer;

class RefreshTransactionRuleIndexService
{
    /**
     *
     * @param SqlQueryBuilder $sqlQueryBuilder
     * @param RuleToConditionTransformer $transformer
     * @param RuleListService $ruleListService
     */
    public function __construct(
        private SqlQueryBuilder $sqlQueryBuilder,
        private RuleToConditionTransformer $transformer,
        private RuleListService $ruleListService
    ) {
    }

    public function refreshAll(int $bankAccountId): void
    {
        DB::transaction(function () use ($bankAccountId) {
            DB::table('rule_transaction')->truncate();


            //$rules = Rule::with(['conditionLink', 'conditionLink.conditionable', 'conditionLink.children'])->get();


            foreach ($this->ruleListService->getRules($bankAccountId) as $rule) {
                $query = Transactions::select('id');

                $this->sqlQueryBuilder->build(
                    $query,
                    $this->transformer->transformToConditionList($rule['condition_link'])
                );
                echo $query->toSql() . PHP_EOL;


                // ConditionQueryBuilder::applyCondition($query, $rule->conditionLink); chat: app-contractpartner-search
                /*
                $transactionIds = $query->pluck('id');


                $insertData = $transactionIds->map(fn($transactionId) => [
                    'rule_id' => $rule->id,
                    'transaction_id' => $transactionId
                ])->toArray();


                foreach (array_chunk($insertData, 5000) as $chunkData) {
                    DB::table('rule_transaction')->insert($chunkData);
                }
                    */
            }
        });
    }

    //todo vielleicht noch eine AddNew
}
