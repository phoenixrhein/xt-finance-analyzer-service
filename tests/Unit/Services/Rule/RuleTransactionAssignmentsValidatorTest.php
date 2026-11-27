<?php

namespace de\xovatec\Tests\financeAnalyzer\Unit\Services\Rule;

use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Enums\RuleTargetType;
use de\xovatec\financeAnalyzer\Enums\TransactionSplitType;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\Console\Output\ConsoleOutputInterface;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\IdField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\Equal;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\Rule\RuleListService;
use de\xovatec\financeAnalyzer\Services\Rule\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Services\Rule\RuleTransactionAssignmentsValidator;
use de\xovatec\Tests\financeAnalyzer\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

class RuleTransactionAssignmentsValidatorTest extends TestCase
{
    public function test_it_validates_transaction_split_rules_with_multiple_actions(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('transactions', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('bank_account_iban');
            $table->string('creditor_iban')->nullable();
            $table->decimal('amount', 13, 2);
        });
        Schema::create('transaction_split', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('transaction_id');
            $table->string('type');
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::create('exclusion_list', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('bank_account_id');
            $table->string('value');
            $table->timestamp('deleted_at')->nullable();
        });

        DB::table('transactions')->insert([
            'id' => 1,
            'bank_account_iban' => 'test-iban',
            'creditor_iban' => 'counterparty-iban',
            'amount' => 50.00,
        ]);
        DB::table('transaction_split')->insert([
            'id' => 1,
            'transaction_id' => 1,
            'type' => TransactionSplitType::OTHER->value,
        ]);

        $conditionList = (new ConditionList)->add(
            new Condition(new IdField, new Equal, '1')
        );
        $rule = [
            'id' => 1,
            'target_type' => RuleTargetType::TRANSACTION_SPLIT->value,
            'condition_link' => [],
            'actions' => [
                ['category_id' => 10, 'cashflow_type' => 'in'],
                ['category_id' => 20, 'cashflow_type' => 'out'],
            ],
        ];

        $ruleListService = Mockery::mock(RuleListService::class);
        $ruleListService->shouldReceive('getRulesWithExpression')
            ->twice()
            ->andReturn([$rule], [$rule]);
        $transformer = Mockery::mock(RuleToConditionTransformer::class);
        $transformer->shouldReceive('transformToConditionList')
            ->once()
            ->with([])
            ->andReturn($conditionList);
        $output = Mockery::mock(ConsoleOutputInterface::class);
        $output->shouldReceive('infoInLn')->once();
        $output->shouldReceive('info')->once()->with(__('cli.rule.validator.validate_assignments_finished'));

        $validator = new RuleTransactionAssignmentsValidator(
            new SqlQueryBuilder,
            $transformer,
            $ruleListService,
            $output
        );
        $bankAccount = new BankAccount(['iban' => 'test-iban', 'bic' => 'test-bic']);
        $bankAccount->id = 1;

        $validator->validateAll($bankAccount, false);
    }
}
