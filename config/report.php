<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Report Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for financial report generation and display.
    |
    */

    'display' => [
        /**
         * Maximum depth of category hierarchy to display in reports.
         * For example: 1 = only root categories, 2 = root + one level of subcategories
         */
        'max_category_depth' => (int) env('REPORT_MAX_CATEGORY_DEPTH', 2),

        /**
         * Whether to display categories with zero amount (no transactions).
         * If false, categories without transactions are hidden from output.
         */
        'show_empty_categories' => (bool) env('REPORT_SHOW_EMPTY_CATEGORIES', false),

        /**
         * Decimal places for currency formatting.
         */
        'currency_decimals' => (int) env('REPORT_CURRENCY_DECIMALS', 2),

        /**
         * Thousand separator for currency formatting (e.g., '.' for 1.000,00)
         */
        'currency_thousand_separator' => env('REPORT_CURRENCY_THOUSAND_SEPARATOR', '.'),

        /**
         * Decimal separator for currency formatting (e.g., ',' for 1.000,00)
         */
        'currency_decimal_separator' => env('REPORT_CURRENCY_DECIMAL_SEPARATOR', ','),

        /**
         * Maximum number of top counterparties to show in the rule assignment overview.
         */
        'rule_assign_top_counterparties_limit' => (int) env('RULE_ASSIGN_TOP_COUNTERPARTIES_LIMIT', 5),
    ],

];
