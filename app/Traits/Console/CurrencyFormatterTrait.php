<?php

namespace de\xovatec\financeAnalyzer\Traits\Console;

/**
 * Provides currency formatting functionality for console output.
 * Formats numeric amounts to localized currency strings based on configuration.
 */
trait CurrencyFormatterTrait
{
    /**
     * Thousand separator for currency formatting.
     *
     * @var string
     */
    private string $thousandSeparator;

    /**
     * Decimal separator for currency formatting.
     *
     * @var string
     */
    private string $decimalSeparator;

    /**
     * Number of decimal places for currency formatting.
     *
     * @var integer
     */
    private int $decimals;

    /**
     * Initialize currency formatting configuration from config.
     * Should be called in the constructor of the using class.
     */
    protected function initializeCurrencyFormatter(): void
    {
        $this->thousandSeparator = config('report.display.currency_thousand_separator', '.');
        $this->decimalSeparator = config('report.display.currency_decimal_separator', ',');
        $this->decimals = config('report.display.currency_decimals', 2);
    }

    /**
     * Format amount according to configuration.
     */
    protected function formatAmount(float $amount): string
    {
        if ($amount === 0.0) {
            return '0 €';
        }

        $isNegative = $amount < 0;
        $absolute = abs($amount);

        $formatted = number_format(
            $absolute,
            $this->decimals,
            $this->decimalSeparator,
            $this->thousandSeparator
        );

        $sign = $isNegative ? '-' : '';

        return "{$sign}{$formatted} €";
    }
}
