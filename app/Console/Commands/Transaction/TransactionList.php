<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Transaction;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Helpers\DateRangeHelper;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Traits\Command\DateRangeParameter;
use de\xovatec\financeAnalyzer\Services\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Services\Expression\CLiErrorHighlighter;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionSyntaxParser;
use de\xovatec\financeAnalyzer\Traits\Command\View\TableConsolePagination;
use de\xovatec\financeAnalyzer\Traits\Command\View\ConditionByManualCreator;
use de\xovatec\financeAnalyzer\Traits\Command\View\ConditionByFinQueryCreator;

use function Laravel\Prompts\select;

class TransactionList extends FinCommand
{
    use TableConsolePagination;
    use DateRangeParameter;
    use BankAccountIdParameter;
    use ConditionByManualCreator;
    use ConditionByFinQueryCreator;

    /**
     *
     * @param FinQueryBuilder $finQueryBuilder
     * @param SqlQueryBuilder $sqlQueryBuilder
     * @param ExpressionSyntaxParser $parser
     * @param CLiErrorHighlighter $errorHighlighter
     * @param RuleToConditionTransformer $transformer
     */
    public function __construct(
        private FinQueryBuilder $finQueryBuilder,
        private SqlQueryBuilder $sqlQueryBuilder,
        private ExpressionSyntaxParser $parser,
        private CLiErrorHighlighter $errorHighlighter,
        private RuleToConditionTransformer $transformer
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    private function getFinQueryBuilder(): FinQueryBuilder
    {
        return $this->finQueryBuilder;
    }

    /**
     * @inheritDoc
     */
    private function getSqlQueryBuilder(): SqlQueryBuilder
    {
        return $this->sqlQueryBuilder;
    }

    /**
     * @inheritDoc
     */
    private function getExpressionSyntaxParser(): ExpressionSyntaxParser
    {
        return $this->parser;
    }

    /**
     * @inheritDoc
     */
    private function getCliErrorHighlighter(): CliErrorHighlighter
    {
        return $this->errorHighlighter;
    }

    /**
     * @inheritDoc
     */
    private function getTransformer(): RuleToConditionTransformer
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
        'mandate_ reference' => null,
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
    protected function process(): void
    {
        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'));
        if (!$bankAccount instanceof BankAccount) {
            return;
        }

        $viewConfig = static::$compactView;
        if ($this->option('full')) {
            $viewConfig = static::$fullView;
        }

        $ignoreIbans = IgnoreList::where('bank_account_id', $this->argument('accountId'))->select('value')->get();

        $conditions = $this->determineCondition($bankAccount, $ignoreIbans);
        $transactions = Transactions::where('bank_account_iban', $bankAccount->iban);

        if ($conditions !== null) {
            $this->getSqlQueryBuilder()->build($transactions, $conditions);
        }

        if (strlen($this->option('range')) > 0) {
            $this->filterDuration($transactions);
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
        $transactions->select($this->getColumns($viewConfig))
            ->orderBy('transaction_date')
            ->orderByDesc('id');

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
     * @param BankAccount $bankAccount
     * @param Collection|null $ignoreIbans
     * @return ConditionList|null
     */
    private function determineCondition(
        BankAccount $bankAccount,
        ?Collection $ignoreIbans = null
    ): ?ConditionList {
        $queryType = select(
            __('cli.transaction.list.query_type.title'),
            [
                'all' => __('cli.transaction.list.query_type.options.all'),
                'manual' => __('cli.transaction.list.query_type.options.manual'),
                'finQuery' => __('cli.transaction.list.query_type.options.fin_query')
            ]
        );

        $conditions = null;
        if ($queryType === 'manual') {
            $conditions = $this->viewConditionByManualCreator($bankAccount, $ignoreIbans);
        } elseif ($queryType === 'finQuery') {
            $conditions = $this->viewConditionByFinQueryCreator($bankAccount, $ignoreIbans);
        }

        return $conditions;
    }

    /**
     *
     * @param Transactions $transactions
     * @return void
     */
    private function filterDuration(Transactions $transactions): void
    {
        $range = $this->prepareRangeParam($this->option('range'));
        if ($range === null) {
            return;
        }

        $from = $range[DateRangeHelper::FROM];
        $to = $range[DateRangeHelper::TO];

        $transactions = $transactions->where('transaction_date', '>=', $from)
            ->where('transaction_date', '<=', $to);
    }
}
