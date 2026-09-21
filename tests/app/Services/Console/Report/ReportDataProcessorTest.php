<?php

namespace de\xovatec\Tests\financeAnalyzer\app\Services\Console\Report;

use Carbon\Carbon;
use de\xovatec\Tests\financeAnalyzer\TestCase;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportDataProcessor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportDataProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('transactions', function (Blueprint $table): void {
            $table->unsignedInteger('id');
            $table->string('bank_account_iban');
            $table->date('transaction_date');
            $table->string('creditor_iban')->nullable();
            $table->decimal('amount', 13, 2);
        });

        Schema::create('transaction_adjustment', function (Blueprint $table): void {
            $table->unsignedInteger('id');
            $table->unsignedInteger('transaction_id');
            $table->date('transaction_date');
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function test_transaction_without_adjustment_uses_original_transaction_date(): void
    {
        $this->insertTransaction(1, '2026-08-15');

        $transactions = $this->loadTransactions('2026-08-01', '2026-08-31');

        $this->assertSame([1], $transactions->pluck('id')->all());
    }

    public function test_transaction_with_adjustment_uses_adjustment_transaction_date(): void
    {
        $this->insertTransaction(1, '2026-08-15');
        DB::table('transaction_adjustment')->insert([
            'id' => 1,
            'transaction_id' => 1,
            'transaction_date' => '2026-09-03',
        ]);

        $this->assertSame([], $this->loadTransactions('2026-08-01', '2026-08-31')->pluck('id')->all());
        $this->assertSame([1], $this->loadTransactions('2026-09-01', '2026-09-30')->pluck('id')->all());
    }

    private function insertTransaction(int $id, string $date): void
    {
        DB::table('transactions')->insert([
            'id' => $id,
            'bank_account_iban' => 'DE123',
            'transaction_date' => $date,
            'creditor_iban' => null,
            'amount' => -100,
        ]);
    }

    private function loadTransactions(string $from, string $to)
    {
        $method = new \ReflectionMethod(ReportDataProcessor::class, 'loadTransactions');
        $method->setAccessible(true);

        return $method->invoke(
            new ReportDataProcessor(),
            new BankAccount(['iban' => 'DE123']),
            Carbon::parse($from),
            Carbon::parse($to),
            false
        );
    }
}
