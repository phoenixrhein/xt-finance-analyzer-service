<?php

namespace de\xovatec\Tests\financeAnalyzer\app\Services\FinQuery\Fields\Transaction;

use de\xovatec\Tests\financeAnalyzer\TestCase;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\AmountField;

class AmountFieldTest extends TestCase
{
    private AmountField $field;

    protected function setUp(): void
    {
        $this->createApplication();
        $this->field = new AmountField();
    }

    public static function validAmountProvider(): array
    {
        return [
            ['000', true, '0'],
            ['12', true, '12'],
            ['12,50', true, '12.50'],
            ['12,5', true, '12.50'],
            ['0,5', true, '0.50'],
            ['007', true, '7'],
        ];
    }

    /**
     * @dataProvider validAmountProvider
     */
    public function testValidAndNormalizedAmounts(string $value, bool $valid, string $normalized): void
    {
        $this->assertTrue($valid);
        $this->assertTrue($this->field->validate($value));
        $this->assertSame($normalized, $this->field->normalizeValue($value));
    }

    public static function invalidAmountProvider(): array
    {
        return [
            ['12,555'],
            ['abc'],
            ['12.555'],
        ];
    }

    /**
     * @dataProvider invalidAmountProvider
     */
    public function testInvalidAmountsAreRejected(string $value): void
    {
        $this->assertFalse($this->field->validate($value));
    }
}
