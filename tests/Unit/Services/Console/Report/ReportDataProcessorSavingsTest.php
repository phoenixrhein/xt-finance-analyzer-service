<?php

namespace de\xovatec\Tests\financeAnalyzer\Unit\Services\Console\Report;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\Console\Report\ReportDataProcessor;
use de\xovatec\Tests\financeAnalyzer\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportDataProcessorSavingsTest extends TestCase
{
    public function test_it_keeps_excluded_iban_transactions_separate_per_savings_account(): void
    {
        $this->createSchema();
        $this->seedReportData();

        $bankAccount = BankAccount::query()->findOrFail(1);
        $periods = [
            ['from' => '2025-01-01', 'to' => '2025-01-31'],
            ['from' => '2025-02-01', 'to' => '2025-02-28'],
        ];

        $reportData = (new ReportDataProcessor)->process($bankAccount, $periods, true);

        $this->assertCount(2, $reportData);
        $this->assertSame(15.0, $reportData[0]->totalIncome);
        $this->assertCount(2, $reportData[0]->savingsAccounts);
        $this->assertSame(-100.0, $reportData[0]->savingsAccounts->firstWhere('exclusionListId', 1)->toExcludedIban);
        $this->assertSame(0.0, $reportData[0]->savingsAccounts->firstWhere('exclusionListId', 1)->fromExcludedIban);
        $this->assertSame(0.0, $reportData[0]->savingsAccounts->firstWhere('exclusionListId', 2)->toExcludedIban);
        $this->assertSame(30.0, $reportData[0]->savingsAccounts->firstWhere('exclusionListId', 2)->fromExcludedIban);
        $this->assertSame(40.0, $reportData[1]->savingsAccounts->firstWhere('exclusionListId', 1)->fromExcludedIban);
        $this->assertSame(-20.0, $reportData[1]->savingsAccounts->firstWhere('exclusionListId', 2)->toExcludedIban);
        $this->assertSame('Sparbuch A', $reportData[0]->savingsAccounts->firstWhere('exclusionListId', 1)->comment);
        $this->assertSame('A12', $reportData[0]->savingsAccounts->firstWhere('exclusionListId', 1)->ibanLastThree);

        $withoutExclusionOption = (new ReportDataProcessor)->process($bankAccount, [$periods[0]], false)->first();
        $this->assertSame(45.0, $withoutExclusionOption->totalIncome);
        $this->assertCount(0, $withoutExclusionOption->savingsAccounts);
    }

    private function createSchema(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('bank_account', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('iban');
            $table->softDeletes();
        });
        Schema::create('cashflow', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('bank_account_id');
            $table->unsignedInteger('in_category_id');
            $table->unsignedInteger('out_category_id');
            $table->softDeletes();
        });
        Schema::create('category', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('parent_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('exclusion_list', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('bank_account_id');
            $table->string('type');
            $table->string('value');
            $table->string('comment')->nullable();
            $table->softDeletes();
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('bank_account_iban');
            $table->date('transaction_date');
            $table->string('creditor_iban');
            $table->decimal('amount', 13, 2);
        });
        Schema::create('transaction_adjustment', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('transaction_id');
            $table->date('transaction_date');
            $table->softDeletes();
        });
        Schema::create('transaction_split', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('transaction_id');
            $table->string('type');
            $table->decimal('amount', 13, 2);
            $table->softDeletes();
        });
        Schema::create('rule_transaction', function (Blueprint $table): void {
            $table->unsignedInteger('rule_id');
            $table->unsignedInteger('transaction_id');
        });
        Schema::create('rule', function (Blueprint $table): void {
            $table->increments('id');
        });
        Schema::create('action', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('rule_id');
            $table->unsignedInteger('category_id');
            $table->string('cashflow_type')->nullable();
        });
    }

    private function seedReportData(): void
    {
        DB::table('bank_account')->insert(['id' => 1, 'iban' => 'DE000']);
        DB::table('category')->insert([
            ['id' => 1, 'name' => 'Einnahmen'],
            ['id' => 2, 'name' => 'Ausgaben'],
        ]);
        DB::table('cashflow')->insert([
            'bank_account_id' => 1,
            'in_category_id' => 1,
            'out_category_id' => 2,
        ]);
        DB::table('exclusion_list')->insert([
            ['id' => 1, 'bank_account_id' => 1, 'type' => 'iban', 'value' => 'DE-SAVINGS-A12', 'comment' => 'Sparbuch A'],
            ['id' => 2, 'bank_account_id' => 1, 'type' => 'iban', 'value' => 'DE-SAVINGS-B34', 'comment' => 'Sparbuch B'],
        ]);
        DB::table('transactions')->insert([
            ['id' => 1, 'bank_account_iban' => 'DE000', 'transaction_date' => '2025-01-10', 'creditor_iban' => 'DE-SAVINGS-A12', 'amount' => -100.00],
            ['id' => 2, 'bank_account_iban' => 'DE000', 'transaction_date' => '2025-01-12', 'creditor_iban' => 'DE-SAVINGS-B34', 'amount' => 30.00],
            ['id' => 3, 'bank_account_iban' => 'DE000', 'transaction_date' => '2025-01-15', 'creditor_iban' => 'DE-REGULAR-99', 'amount' => 15.00],
            ['id' => 4, 'bank_account_iban' => 'DE000', 'transaction_date' => '2025-02-10', 'creditor_iban' => 'DE-SAVINGS-A12', 'amount' => 40.00],
            ['id' => 5, 'bank_account_iban' => 'DE000', 'transaction_date' => '2025-02-15', 'creditor_iban' => 'DE-SAVINGS-B34', 'amount' => -20.00],
        ]);
    }
}
