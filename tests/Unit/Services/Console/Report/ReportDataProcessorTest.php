<?php

namespace de\xovatec\Tests\financeAnalyzer\Unit\Services\Console\Report;

use de\xovatec\Tests\financeAnalyzer\TestCase;
use de\xovatec\financeAnalyzer\Enums\TransactionSplitType;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportDataProcessor;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * @param array<int, array{type: TransactionSplitType, amount: float}> $splits
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

        return (new ReportDataProcessor())->calculateSplitAmounts($transaction);
    }
}
