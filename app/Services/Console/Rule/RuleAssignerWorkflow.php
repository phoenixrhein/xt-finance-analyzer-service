<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Rule;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use de\xovatec\financeAnalyzer\Helpers\FilterTransactionDurationHelper;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\IgnoreList;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Console\Category\ManageConsoleService;
use de\xovatec\financeAnalyzer\Services\Console\Category\TreeViewConsoleService;
use de\xovatec\financeAnalyzer\Services\Console\Rule\UnmatchedTransactionsDisplayService;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\CliErrorHighlighter;
use de\xovatec\financeAnalyzer\Services\Rule\Expression\ExpressionSyntaxParser;
use de\xovatec\financeAnalyzer\Services\Rule\RuleDataManager;
use de\xovatec\financeAnalyzer\Services\Rule\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Services\UnmatchedTransactionsService;
use de\xovatec\financeAnalyzer\Traits\Command\View\ConditionByFinQueryCreator;
use de\xovatec\financeAnalyzer\Traits\Command\View\ConditionByManualCreator;
use de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces\ProvidesAccountListQueryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Laravel\Prompts\select;

class RuleAssignerWorkflow extends AbstractIOService implements ProvidesAccountListQueryInterface
{
    use ConditionByManualCreator;
    use ConditionByFinQueryCreator;

    /**
     *
     * @var boolean
     */
    private bool $overlapMatches = false;

    public function __construct(
        private AccountListQuery $accountListQuery,
        private UnmatchedTransactionsService $unmatchedTransactionsService,
        private FinQueryBuilder $finQueryBuilder,
        private ExpressionSyntaxParser $parser,
        private CliErrorHighlighter $errorHighlighter,
        private RuleToConditionTransformer $transformer,
        private SqlQueryBuilder $sqlQueryBuilder,
        private RuleDataManager $ruleDataManager,
        private ManageConsoleService $manageConsoleService,
        private TreeViewConsoleService $treeViewConsoleService,
        private UnmatchedTransactionsDisplayService $unmatchedTransactionsDisplayService
    ) {
        parent::__construct();
        $this->setDisplayLimit(7);
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
     *
     * @return void
     */
    public function assignRule(BankAccount $bankAccount, FinCommand $command): void
    {

        $ignoreIbans = IgnoreList::where('bank_account_id', $bankAccount->id)->select('value')->get();

        $this->viewUnmatchedTransactions($bankAccount, $ignoreIbans, $command);

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

        $this->treeViewConsoleService->displayCashflowTrees($cashflow);
        $this->manageConsoleService->manage($bankAccount->id, __('cli.rule.assign.cat_mgmt_continue_button_text'));
        $selectedCategoryId = $this->manageConsoleService->findAndSelectCategory($cashflow);

        $this->saveRule(
            $this->viewInput('Name der Regel', 'required|min:1|unique:rule,name'),
            $selectedCategoryId,
            $conditionList,
            $bankAccount->id
        );

        if (
            $this->unmatchedTransactionsService->getTotalUnmatchedTransactions(
                $bankAccount,
                $ignoreIbans
            )->count() > 0
        ) {
            $continue = select(
                '',
                [
                    'yes' => 'Weitere Regel erstellen',
                    'no' => 'Beenden'
                ]
            );

            if ($continue === 'yes') {
                $this->assignRule($bankAccount, $command);
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
     * @param FinCommand $command
     * @return void
     */
    private function viewUnmatchedTransactions(
        BankAccount $bankAccount,
        Collection $ignoreIbans,
        FinCommand $command
    ): void {
        $viewConfig = TransactionList::$compactView;
        $total = null;
        $cursor = null;

        do {
            $unmatchedTransactions = $this->buildBaseUnmatchedTransactionsQuery($bankAccount, $ignoreIbans);

            if ($total === null) {
                $this->unmatchedTransactionsDisplayService->displayUnmatchedTransactionsOverview(
                    $this->unmatchedTransactionsService->getUnmatchedTransactions(
                        CopyBuilderQueryHelper::copy($unmatchedTransactions)
                    ),
                );
            }

            if (strlen($this->option('range')) > 0) {
                $unmatchedTransactions = FilterTransactionDurationHelper::applyFilter(
                    $unmatchedTransactions,
                    $this->option('range'),
                    $command
                );
            }

            $unmatchedTransactions = $this->unmatchedTransactionsService->getUnmatchedTransactions(
                $unmatchedTransactions
            );

            if ($total === null) {
                $this->emptyLn();
                $total = (clone $unmatchedTransactions)->toBase()->count();
                $this->line(__('cli.rule.assign.total_found', ['count' => $total]));
            }

            $paginatedTransactions = $unmatchedTransactions->select(array_keys($viewConfig))
                ->cursorPaginate(10, ['*'], 'page', $cursor);

            $col = new Collection();
            foreach ($paginatedTransactions->items() as $item) {
                $col->push($item);
            }

            $this->tableConsolePagination(
                $col,
                $viewConfig,
                null,
                'cli.transaction.base.table.header.'
            );

            $cursor = $paginatedTransactions->nextCursor();
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
     * @param BankAccount $bankAccount
     * @param Collection $ignoreIbans
     * @return Builder
     */
    private function buildBaseUnmatchedTransactionsQuery(BankAccount $bankAccount, Collection $ignoreIbans): Builder
    {
        $transactions = Transactions::where('bank_account_iban', $bankAccount->iban)->orderBy('id');

        if ($ignoreIbans->isNotEmpty()) {
            $transactions = $transactions->whereNotIn('creditor_iban', $ignoreIbans->toArray());
        }

        return $transactions;
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
     * @param SupportCollection<int, Model> $data
     * @return SupportCollection<int, Model>
     */
    protected function formatData(SupportCollection $data): SupportCollection
    {
        return $data->map(function ($row) {
            $coloring = ($row->getAttribute('rule_id') ?? 0) > 0;
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
