<?php

namespace de\xovatec\Tests\financeAnalyzer\Unit\Services\Console\Report;

use de\xovatec\financeAnalyzer\Enums\TransactionSplitType;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportDataProcessor;
use de\xovatec\Tests\financeAnalyzer\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportDataProcessorTest extends TestCase
{
    public function test_transaction_without_splits_keeps_original_amount(): void
    {
        $amounts = $this->calculateAmounts(120.00, []);

        $this->assertSame([
            'remaining' => 120.0,
            'cash_payout' => 0.0,
            'other' => 0.0,
        ], $amounts);
    }

    public function test_cash_payout_and_other_splits_reduce_the_original_category(): void
    {
        $amounts = $this->calculateAmounts(-120.00, [
            ['type' => TransactionSplitType::CASH_PAYOUT, 'amount' => 20.00],
            ['type' => TransactionSplitType::OTHER, 'amount' => 30.00],
        ]);

        $this->assertSame(70.0, $amounts['remaining']);
        $this->assertSame(20.0, $amounts['cash_payout']);
        $this->assertSame(30.0, $amounts['other']);
        $this->assertSame(120.0, array_sum($amounts));
    }

    public function test_income_split_amounts_are_reported_without_a_negative_sign(): void
    {
        $amounts = $this->calculateAmounts(120.00, [
            ['type' => TransactionSplitType::CASH_PAYOUT, 'amount' => 20.00],
        ]);

        $this->assertSame(100.0, $amounts['remaining']);
        $this->assertSame(20.0, $amounts['cash_payout']);
        $this->assertGreaterThanOrEqual(0, $amounts['remaining']);
        $this->assertGreaterThanOrEqual(0, $amounts['cash_payout']);
    }

    public function test_transactions_use_the_category_matching_their_cashflow_type(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('transactions', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->decimal('amount', 13, 2);
        });
        Schema::create('rule_transaction', function (Blueprint $table): void {
            $table->unsignedInteger('rule_id');
            $table->unsignedInteger('transaction_id');
        });
        Schema::create('rule', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
        });
        Schema::create('action', function (Blueprint $table): void {
            $table->unsignedInteger('rule_id');
            $table->unsignedInteger('category_id');
            $table->string('cashflow_type')->nullable();
        });

        DB::table('transactions')->insert([
            ['id' => 1, 'amount' => 120.00],
            ['id' => 2, 'amount' => -75.00],
            ['id' => 3, 'amount' => -20.00],
        ]);
        DB::table('rule')->insert([
            ['id' => 1],
            ['id' => 2],
        ]);
        DB::table('rule_transaction')->insert([
            ['rule_id' => 1, 'transaction_id' => 1],
            ['rule_id' => 1, 'transaction_id' => 2],
            ['rule_id' => 2, 'transaction_id' => 3],
        ]);
        DB::table('action')->insert([
            ['rule_id' => 1, 'category_id' => 10, 'cashflow_type' => 'in'],
            ['rule_id' => 1, 'category_id' => 20, 'cashflow_type' => 'out'],
            ['rule_id' => 2, 'category_id' => 30, 'cashflow_type' => null],
        ]);

        $transactions = new Collection([
            $this->transactionWithAmount(1, 120.00),
            $this->transactionWithAmount(2, -75.00),
            $this->transactionWithAmount(3, -20.00),
        ]);
        $groupTransactions = new \ReflectionMethod(ReportDataProcessor::class, 'groupTransactionsByCategory');

        $this->assertSame(
            [10 => 120.0, 20 => 75.0, 30 => 20.0],
            $groupTransactions->invoke(new ReportDataProcessor, $transactions)
        );
    }

    private function transactionWithAmount(int $id, float $amount): Transactions
    {
        $transaction = new Transactions(['amount' => $amount]);
        $transaction->id = $id;
        $transaction->setRelation('transactionSplit', new Collection);

        return $transaction;
    }

    /**
     * @param  array<int, array{type: TransactionSplitType, amount: float}>  $splits
     * @return array{remaining: float, cash_payout: float, other: float}
     */
    private function calculateAmounts(float $transactionAmount, array $splits): array
    {
        $transaction = new Transactions(['amount' => $transactionAmount]);
        $transaction->setRelation(
            'transactionSplit',
            new Collection(array_map(
                fn (array $split): TransactionSplit => new TransactionSplit([
                    'type' => $split['type'],
                    'amount' => $split['amount'],
                ]),
                $splits
            ))
        );

        return (new ReportDataProcessor)->calculateSplitAmounts($transaction);
    }
}
