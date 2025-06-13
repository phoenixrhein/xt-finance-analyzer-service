<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Rule;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Transactions;
use Illuminate\Support\Collection as SupportCollection;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\Rule\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Services\Rule\RuleDataManager;
use de\xovatec\financeAnalyzer\Services\UnmatchedTransactionsService;
use de\xovatec\financeAnalyzer\Traits\Command\BankAccountIdParameter;
use de\xovatec\financeAnalyzer\Helpers\FilterTransactionDurationHelper;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\CliErrorHighlighter;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\ExpressionSyntaxParser;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;
use de\xovatec\financeAnalyzer\Traits\Command\View\ConditionByManualCreator;
use de\xovatec\financeAnalyzer\Services\Console\Category\ManageConsoleService;
use de\xovatec\financeAnalyzer\Traits\Command\View\ConditionByFinQueryCreator;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;

use function Laravel\Prompts\select;

class RuleTransactionAssigner extends FinCommand implements ProvidesAccountListQueryInterface
{
    use BankAccountIdParameter;
    use ConditionByManualCreator;
    use ConditionByFinQueryCreator;

    /**
     *
     * @param AccountListQuery $accountListQuery
     * @param UnmatchedTransactionsService $unmatchedTransactionsService
     * @param FinQueryBuilder $finQueryBuilder
     * @param ExpressionSyntaxParser $parser
     * @param CliErrorHighlighter $errorHighlighter
     * @param RuleToConditionTransformer $transformer
     * @param SqlQueryBuilder $sqlQueryBuilder
     * @param ManageConsoleService $manageConsoleService
     * @param RuleDataManager $ruleDataManager
     */
    public function __construct(
        private AccountListQuery $accountListQuery,
        private UnmatchedTransactionsService $unmatchedTransactionsService,
        private FinQueryBuilder $finQueryBuilder,
        private ExpressionSyntaxParser $parser,
        private CliErrorHighlighter $errorHighlighter,
        private RuleToConditionTransformer $transformer,
        private SqlQueryBuilder $sqlQueryBuilder,
        private ManageConsoleService $manageConsoleService,
        private RuleDataManager $ruleDataManager
    ) {
        parent::__construct();
        $this->setDisplayLimit(7);
        $this->manageConsoleService->setIo($this);
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
     * @return AccountListQuery
     */
    public function getAccountListQuery(): AccountListQuery
    {
        return $this->accountListQuery;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:rule-assign {accountId : [:cli.base.param.account_id:]} ' .
        '{--range= : [:cli.param.date_range.description:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.rule.assign.description';

    /**
     *
     * @var boolean
     */
    private bool $overlapMatches = false;

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $this->assignRule();
    }

    /**
     *
     * @return void
     */
    private function assignRule(): void
    {
        $bankAccount = $this->getBankAccount((int)$this->argument('accountId'), true);
        $ignoreIbans = IgnoreList::where('bank_account_id', $this->argument('accountId'))->select('value')->get();

        $this->viewUnmatchedTransactions($bankAccount, $ignoreIbans);

        $queryType = select(
            __('cli.transaction.list.query_type.title'),
            [
                'manual' => __('cli.transaction.list.query_type.options.manual'),
                'finQuery' => __('cli.transaction.list.query_type.options.fin_query')
            ]
        );

        $transactions = Transactions::where('bank_account_iban', $bankAccount->iban)->orderBy('id');

        if ($ignoreIbans->isNotEmpty()) {
            $transactions = $transactions->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        }

        $transactions->leftJoin('rule_transaction', 'transactions.id', '=', 'rule_transaction.transaction_id');

        $viewConfig = TransactionList::$compactView;
        $viewConfigColumns = array_keys($viewConfig);
        array_push($viewConfigColumns, 'rule_transaction.rule_id');
        $transactions->select($viewConfigColumns);

        $conditionList = $this->createRuleCondition($transactions, $queryType);

        $cashflow = Cashflow::where('bank_account_id', $bankAccount->id)->first();
        if (!$cashflow instanceof Cashflow) {
            $this->emptyLn();
            $this->error(__('cli.category.base.error.not_found_cashflow', ['bankAccountId' => $bankAccount->id]));
            return;
        }

        $this->manageConsoleService->getTreeViewConsoleService()->displayCashflowTrees($cashflow);
        $this->manageConsoleService->manage($bankAccount->id, __('cli.rule.assign.cat_mgmt_continue_button_text'));
        $selectedCategoryId = $this->manageConsoleService->findAndSelectCategory($cashflow);

        $this->saveRule(
            $this->viewInput('Name der Regel', 'required|min:1|unique:rule,name'),
            $selectedCategoryId,
            $conditionList,
            $bankAccount->id
        );

        if ($this->unmatchedTransactionsService->getTotalUnmatchedTransactions(
            $bankAccount,
            $ignoreIbans
        )->count() > 0) {
            $continue = select(
                '',
                [
                    'yes' => 'Weitere Regel erstellen',
                    'no' => 'Beenden'
                ]
            );

            if ($continue === 'yes') {
                $this->assignRule();
            }
        }
    }

    /**
     *
     * @param string $name
     * @param integer $categoryId
     * @param ConditionList $conditionList
     * @param integer $bankAccountId
     * @return void
     */
    private function saveRule(
        string $name,
        int $categoryId,
        ConditionList $conditionList,
        int $bankAccountId
    ): void {
        try {
            DB::beginTransaction();
            $id = $this->ruleDataManager->saveRule(
                $name,
                $categoryId,
                $conditionList,
                $bankAccountId
            );
            DB::commit();
            $this->info(__('cli.rule.assign.result.info', ['id' => $id]));
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->error(__('cli.rule.assign.result.error'));
        }
    }

    /**
     *
     * @param BankAccount $bankAccount
     * @param Collection $ignoreIbans
     * @return void
     */
    private function viewUnmatchedTransactions(BankAccount $bankAccount, Collection $ignoreIbans): void
    {
        $viewConfig = TransactionList::$compactView;
        $total = null;
        $cursor = null;

        do {
            $unmatchedTransactions = Transactions::where('bank_account_iban', $bankAccount->iban)->orderBy('id');

            if ($ignoreIbans->isNotEmpty()) {
                $unmatchedTransactions = $unmatchedTransactions->whereNotIn('creditor_iban', $ignoreIbans->toArray());
            }

            if ($total === null) {
                $totalUnmatchedTransactions = $this->unmatchedTransactionsService->getUnmatchedTransactions(
                    CopyBuilderQueryHelper::copy($unmatchedTransactions)
                )->count();
                if ($totalUnmatchedTransactions > 0) {
                    $this->emptyLn();
                    $this->alert(__(
                        'cli.rule.assign.count_unmatched_transactions',
                        ['count' => $totalUnmatchedTransactions]
                    ));
                    $this->halt();
                }
            }

            if (strlen($this->option('range')) > 0) {
                $unmatchedTransactions = FilterTransactionDurationHelper::applyFilter(
                    $unmatchedTransactions,
                    $this->option('range'),
                    $this
                );
            }

            $unmatchedTransactions = $this->unmatchedTransactionsService->getUnmatchedTransactions(
                $unmatchedTransactions
            );

            if ($total === null) {
                $this->emptyLn();
                $total = (clone $unmatchedTransactions)->toBase()->get()->count();
                $this->line(__('cli.rule.assign.total_found', ['count' => $total]));
            }

            $unmatchedTransactions = $unmatchedTransactions->select(array_keys($viewConfig))
                ->cursorPaginate(10, ['*'], 'page', $cursor);

            $col = new Collection();
            foreach ($unmatchedTransactions->items() as $item) {
                $col->push($item);
            }

            $this->tableConsolePagination(
                $col,
                $viewConfig,
                null,
                'cli.transaction.base.table.header.'
            );

            $cursor = $unmatchedTransactions->nextCursor();
        } while (
            $cursor !== null
            && $this->confirmPrompt(
                __('cli.rule.assign.select_more_data_or_add_rule.text'),
                true,
                __('cli.rule.assign.select_more_data_or_add_rule.options.more_data'),
                __('cli.rule.assign.select_more_data_or_add_rule.options.add_rule')
            )
        );
    }

    /**
     *
     * @param Builder $transactions
     * @param string $queryType
     * @return ConditionList
     */
    private function createRuleCondition(Builder $transactions, string $queryType): ConditionList
    {
        if ($queryType === 'manual') {
            $conditions = $this->viewConditionByManualCreator($transactions);
        } else {
            $conditions = $this->viewConditionByFinQueryCreator($transactions);
        }

        return $conditions;
    }

    /**
     *
     * @param Collection $data
     * @return Collection
     */
    protected function formatData(SupportCollection $data): SupportCollection
    {

        return $data->map(function ($row) {
            $coloring = $row->rule_id > 0;
            $attributes = collect($row->getAttributes())->map(function ($item, $key) use ($coloring) {
                if ($key === 'id' || $coloring === false) {
                    return $item;
                }
                return "<fg=yellow>$item</>";
            });

            $row->setRawAttributes($attributes->toArray());
            return $row->makeHidden('rule_id');
        });
    }

    /**
     *
     * @param Builder $clonedTransactions
     * @return void
     */
    protected function onTotalResult(Builder $clonedTransactions): void
    {
        $clonedTransactions->whereNotNull('rule_transaction.rule_id');
        $found = $clonedTransactions->count();
        $this->overlapMatches = $found > 0;
        if ($found > 0) {
            $this->emptyLn();
            $this->alert(__('cli.rule.assign.found_already_matched', ['count' => $found]));
            $this->halt();
        }
    }
}
