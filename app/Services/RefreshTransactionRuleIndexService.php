<?php

namespace de\xovatec\financeAnalyzer\Services;

use Illuminate\Support\Facades\DB;
use de\xovatec\financeAnalyzer\Models\Rule;
use de\xovatec\financeAnalyzer\Models\Transactions;

class RefreshTransactionRuleIndexService
{
    public function refreshAll(): void
    {
        DB::transaction(function () {
            DB::table('rule_transaction')->truncate();


            $rules = Rule::with(['conditionLink', 'conditionLink.conditionable', 'conditionLink.children'])->get();


            foreach ($rules as $rule) {
                $query = Transactions::select('id');
                // ConditionQueryBuilder::applyCondition($query, $rule->conditionLink); chat: app-contractpartner-search
                $transactionIds = $query->pluck('id');


                $insertData = $transactionIds->map(fn($transactionId) => [
                    'rule_id' => $rule->id,
                    'transaction_id' => $transactionId
                ])->toArray();


                foreach (array_chunk($insertData, 5000) as $chunkData) {
                    DB::table('rule_transaction')->insert($chunkData);
                }
            }
        });
    }

    //todo vielleicht noch eine AddNew
}
