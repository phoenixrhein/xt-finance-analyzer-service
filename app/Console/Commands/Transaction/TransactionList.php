<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Transaction;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\Rule\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Helpers\FilterTransactionDurationHelper;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\CliErrorHighlighter;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\ExpressionSyntaxParser;
use de\xovatec\financeAnalyzer\Traits\Command\View\TableConsolePagination;
use de\xovatec\financeAnalyzer\Traits\Command\View\ConditionByManualCreator;
use de\xovatec\financeAnalyzer\Traits\Command\View\ConditionByFinQueryCreator;

use function Laravel\Prompts\select;

class TransactionList extends FinCommand
{
    use TableConsolePagination;
    use BankAccountIdParameter;
    use ConditionByManualCreator;
    use ConditionByFinQueryCreator;

    /**
     *
     * @param FinQueryBuilder $finQueryBuilder
     * @param SqlQueryBuilder $sqlQueryBuilder
     * @param ExpressionSyntaxParser $parser
     * @param CliErrorHighlighter $errorHighlighter
     * @param RuleToConditionTransformer $transformer
     */
    public function __construct(
        private FinQueryBuilder $finQueryBuilder,
        private SqlQueryBuilder $sqlQueryBuilder,
        private ExpressionSyntaxParser $parser,
        private CliErrorHighlighter $errorHighlighter,
        private RuleToConditionTransformer $transformer
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function getFinQueryBuilder(): FinQueryBuilder
    {
        return $this->finQueryBuilder;
    }

    /**
     * @inheritDoc
     */
    protected function getSqlQueryBuilder(): SqlQueryBuilder
    {
        return $this->sqlQueryBuilder;
    }

    /**
     * @inheritDoc
     */
    protected function getExpressionSyntaxParser(): ExpressionSyntaxParser
    {
        return $this->parser;
    }

    /**
     * @inheritDoc
     */
    protected function getCliErrorHighlighter(): CliErrorHighlighter
    {
        return $this->errorHighlighter;
    }

    /**
     * @inheritDoc
     */
    protected function getTransformer(): RuleToConditionTransformer
    {
        return $this->transformer;
    }

    /**
     *
     * @var array
     */
    private static $fullView = [
        'id' => null,
        'transaction_date' => null,
        'exchange_date' => null,
        'transaction_type' => null,
        'reason_for_payment' => null,
        'creditor_id' => null,
        'mandate_reference' => null,
        'customer_reference' => null,
        'collector_reference' => null,
        'debit_original_amount' => null,
        'reimbursement_of_expenses_return_debit' => null,
        'beneficiary_payee' => null,
        'creditor_iban' => null,
        'creditor_bic' => null,
        'amount' => null,
        'currency' => null,
        'note' => null
    ];

    /**
     *
     * @var array
     */
    public static $compactView = [
        'id' => [
            'width' => 7
        ],
        'transaction_date' => [
            'width' => 10
        ],
        'transaction_type' => [
            'width' => 35
        ],
        'reason_for_payment' => [
            'width' => '50%'
        ],
        'beneficiary_payee' => [
            'width' => '50%'
        ],
        'creditor_iban' => [
            'width' => 27
        ],
        'amount' => [
            'width' => 8
        ],
        'currency' => [
            'width' => 7
        ],
        'note' => [
            'width' => 9
        ]
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:transaction-list {accountId : [:cli.base.param.account_id:]} {--full} {--noLimit}' .
        ' {--range= : [:cli.param.date_range.description:]} {--limit=25}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.transaction.list.description';

    /**
     *
     * @param array $config
     * @return array
     */
    private function getColumns(array $config): array
    {
        return array_keys($config);
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'));
        if (!$bankAccount instanceof BankAccount) {
            return;
        }

        $viewConfig = static::$compactView;
        if ($this->option('full')) {
            $viewConfig = self::$fullView;
        }

        $ignoreIbans = IgnoreList::where('bank_account_id', $this->argument('accountId'))->select('value')->get();
        $transactions = Transactions::where('bank_account_iban', $bankAccount->iban)
            ->orderBy('transaction_date')
            ->orderByDesc('id');

        if (strlen($this->option('range')) > 0) {
            FilterTransactionDurationHelper::applyFilter($transactions, $this->option('range'), $this);
        }

        $conditions = $this->determineCondition(CopyBuilderQueryHelper::copy($transactions), $ignoreIbans);

        if ($conditions !== null) {
            $this->getSqlQueryBuilder()->build($transactions, $conditions);
        }

        $this->displayList($transactions, $viewConfig);
        $this->displayListFooter($transactions, $ignoreIbans);
    }

    /**
     *
     * @param Builder $transactions
     * @param array $viewConfig
     * @return void
     */
    private function displayList(Builder $transactions, array $viewConfig): void
    {
        $transactions->select($this->getColumns($viewConfig));

        $this->tableConsolePagination(
            $transactions->get(),
            $viewConfig,
            $this->option('noLimit') ? null : $this->option('limit'),
            'cli.transaction.base.table.header.'
        );
    }

    /**
     *
     * @param Builder $transactions
     * @param Collection $ignoreIbans
     * @return void
     */
    private function displayListFooter(Builder $transactions, Collection $ignoreIbans): void
    {
        $sum = 0;
        if ($ignoreIbans->isNotEmpty()) {
            $transactions = $transactions->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        }
        foreach (Arr::pluck($transactions->get()->toArray(), 'amount') as $amount) {
            $sum = round($sum + $amount, 2);
        }
        $totalAmount = number_format($sum, 2, ',', '');
        $this->info(
            __('cli.base.count') . ': ' . count($transactions->get()->toArray())
                . ' / ' . __('cli.base.amount') . ': ' . $totalAmount
        );
    }

    /**
     *
     * @param Builder $transactions
     * @param Collection $ignoreIbans
     * @return ConditionList|null
     */
    private function determineCondition(Builder $transactions, Collection $ignoreIbans): ?ConditionList
    {
        $queryType = select(
            __('cli.transaction.list.query_type.title'),
            [
                'all' => __('cli.transaction.list.query_type.options.all'),
                'manual' => __('cli.transaction.list.query_type.options.manual'),
                'finQuery' => __('cli.transaction.list.query_type.options.fin_query')
            ]
        );

        if ($ignoreIbans->isNotEmpty()) {
            $transactions->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        }

        $conditions = null;
        if ($queryType === 'manual') {
            $conditions = $this->viewConditionByManualCreator($transactions);
        } elseif ($queryType === 'finQuery') {
            $conditions = $this->viewConditionByFinQueryCreator($transactions);
        }

        return $conditions;
    }
}
