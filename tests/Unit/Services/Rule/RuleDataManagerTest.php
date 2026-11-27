<?php

namespace de\xovatec\Tests\financeAnalyzer\Unit\Services\Rule;

use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Enums\ConditionType;
use de\xovatec\financeAnalyzer\Enums\RuleTargetType;
use de\xovatec\financeAnalyzer\Models\Action;
use de\xovatec\financeAnalyzer\Services\Rule\RefreshTransactionRuleIndexService;
use de\xovatec\financeAnalyzer\Services\Rule\RuleDataManager;
use de\xovatec\financeAnalyzer\Services\Rule\RuleToConditionTransformer;
use de\xovatec\Tests\financeAnalyzer\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Mockery;

class RuleDataManagerTest extends TestCase
{
    public function test_saving_a_rule_requires_at_least_one_category(): void
    {
        $manager = new RuleDataManager(
            Mockery::mock(RuleToConditionTransformer::class),
            Mockery::mock(RefreshTransactionRuleIndexService::class)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one category must be provided.');

        $manager->saveRuleForTarget(
            'Test rule',
            null,
            null,
            new ConditionList,
            1,
            RuleTargetType::TRANSACTION
        );
    }

    public function test_saving_a_rule_creates_an_action_for_each_provided_category(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('category', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
        });
        Schema::create('condition', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('field_identifier');
            $table->string('compare_operator');
            $table->string('value');
        });
        Schema::create('condition_link', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('foreign_id');
            $table->string('condition_type');
            $table->unsignedBigInteger('condition_link_id')->nullable();
            $table->string('link_operator')->nullable();
        });
        Schema::create('rule', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('name');
            $table->unsignedBigInteger('condition_link_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->string('target_type');
        });
        Schema::create('action', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('category_id');
            $table->string('cashflow_type')->nullable();
        });
        Schema::create('bank_account', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('iban');
            $table->string('bic');
        });

        DB::table('category')->insert([
            ['id' => 10, 'name' => 'Income'],
            ['id' => 20, 'name' => 'Expense'],
        ]);
        DB::table('bank_account')->insert([
            'id' => 1,
            'iban' => 'test-iban',
            'bic' => 'test-bic',
        ]);

        $transformer = Mockery::mock(RuleToConditionTransformer::class);
        $transformer->shouldReceive('transformToArray')->once()->andReturn([
            'conditionType' => ConditionType::condition,
            'condition' => [
                'field' => 'id',
                'comparer' => '=',
                'value' => '1',
            ],
            'linkTo' => null,
            'logicOperator' => null,
        ]);
        $indexService = Mockery::mock(RefreshTransactionRuleIndexService::class);
        $indexService->shouldReceive('addRule')->once();

        $manager = new RuleDataManager($transformer, $indexService);
        $manager->saveRuleForTarget(
            'Test rule',
            10,
            20,
            new ConditionList,
            1,
            RuleTargetType::TRANSACTION
        );

        $actions = Action::query()->orderBy('cashflow_type')->get()->pluck('category_id', 'cashflow_type')->all();
        $this->assertSame(['in' => 10, 'out' => 20], $actions);
    }
}
