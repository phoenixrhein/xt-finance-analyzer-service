<?php

namespace de\xovatec\Tests\financeAnalyzer\app\Services\Expression;

use de\xovatec\Tests\financeAnalyzer\TestCase;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Parser\ErrorReport;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionSyntaxParser;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionBaseValidator;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;

class ExpressionSyntaxParserTest extends TestCase
{
    /**
     *
     * @var ExpressionSyntaxParser
     */
    private ExpressionSyntaxParser $parser;

    /**
     *
     * @var RuleToConditionTransformer
     */
    private RuleToConditionTransformer $transformer;

    /**
     *
     * @var FinQueryBuilder
     */
    private FinQueryBuilder $queryBuilder;

    /**
     *
     * @var SqlQueryBuilder
     */
    private SqlQueryBuilder $sqlQueryBuilder;

    /**
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->createApplication();

        /** @var ExpressionBaseValidator $validatorMock */
        $validatorMock = $this->createMock(ExpressionBaseValidator::class);
        /** @var ErrorReport $errorReportMock */
        $errorReportMock = $this->createMock(ErrorReport::class);

        $this->parser = new ExpressionSyntaxParser($validatorMock, $errorReportMock);
        $this->transformer = new RuleToConditionTransformer();
        $this->queryBuilder = new FinQueryBuilder();
        $this->sqlQueryBuilder = new SqlQueryBuilder();
    }

    /**
     *
     * @return array
     */
    public static function expressionProvider(): array
    {
        return [
            [
                "amount = '50'",
                null,
                'select * from `transactions` where (`amount` = ?)'
            ],
            [
                "amount > '100' AND amount < 200",
                "amount > '100' AND amount < '200'",
                "select * from `transactions` where (`amount` > ? and `amount` < ?)"
            ],
            [
                "amount = 50",
                "amount = '50'",
                "select * from `transactions` where (`amount` = ?)"
            ],
            [
                "(amount  >  '1555'  AND amount = '1700' )  OR  (amount =  12 AND amount = 13)",
                "(amount > '1555' AND amount = '1700') OR (amount = '12' AND amount = '13')",
                "select * from `transactions` where ((`amount` > ? and `amount` = ?) or (`amount` = ? and `amount` = ?))"
            ],
            [
                "(amount > '1600' AND amount < '1800') OR amount < '-1200'",
                null,
                "select * from `transactions` where ((`amount` > ? and `amount` < ?) or `amount` < ?)"
            ],
            [
                "amount = '2' OR (amount > '1555' AND amount < '1700')",
                null,
                "select * from `transactions` where (`amount` = ? or (`amount` > ? and `amount` < ?))"
            ]
        ];
    }

    /**
     * @dataProvider expressionProvider
     *
     * @param string $expression
     * @param string|null $expectedQuery
     * @return void
     */
    public function testParseToQuery(string $expression, ?string $expectedQuery): void
    {
        $parsed = $this->parser->parse($expression);
        $this->assertIsArray($parsed, "Parsing failed for expression: $expression");
        
        $conditionList = $this->transformer->transform($parsed);
        $this->assertInstanceOf(ConditionList::class, $conditionList);
        
        $query = $this->queryBuilder->build($conditionList);
        $this->assertSame(
            $expectedQuery ?? $expression,
            $query,
            "Query does not match expected result for expression: {$expression}"
        );
    }

    /**
     * @dataProvider expressionProvider
     *
     * @param string $expression
     * @param string|null $expectedQuery
     * @param string|null $expectedSqlQuery
     * @return void
     */
    public function testParseToSqlQuery(string $expression, ?string $expectedQuery, ?string $expectedSqlQuery): void
    {
        $parsed = $this->parser->parse($expression);
        $this->assertIsArray($parsed, "Parsing failed for expression: {$expression}");
        
        $conditionList = $this->transformer->transform($parsed);
        
        $query = Transactions::query();
        $this->sqlQueryBuilder->build($query, $conditionList);
        $this->assertSame(
            $expectedSqlQuery,
            $query->toSql(),
            "SQL Query does not match expected result for expression: {$expectedSqlQuery}"
        );
    }
}
