<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Rule;

use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Condition;
use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use de\xovatec\financeAnalyzer\Helpers\FilterTransactionDurationHelper;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\ExclusionList;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Models\TransactionSplit;
use de\xovatec\financeAnalyzer\Enums\RuleTargetType;
use de\xovatec\financeAnalyzer\Enums\TransactionSplitType;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Console\Category\ManageConsoleService;
use de\xovatec\financeAnalyzer\Services\Console\Category\TreeViewConsoleService;
use de\xovatec\financeAnalyzer\Services\Console\Rule\UnmatchedTransactionsDisplayService;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\SqlQueryBuilder;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\IdField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Operators\Equal;
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

    private RuleTargetType $targetType = RuleTargetType::TRANSACTION;

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
        $this->targetType = RuleTargetType::from(select(
            __('cli.rule.assign.target_type.title'),
            [
                RuleTargetType::TRANSACTION->value => __('cli.rule.assign.target_type.transaction'),
                RuleTargetType::TRANSACTION_SPLIT->value => __('cli.rule.assign.target_type.transaction_split'),
            ]
        ));

        $exclusionIbans = ExclusionList::where('bank_account_id', $bankAccount->id)->select('value')->get();

        if ($this->targetType === RuleTargetType::TRANSACTION) {
            $this->viewUnmatchedTransactions($bankAccount, $exclusionIbans, $command);
        } else {
            $this->viewUnmatchedTransactionSplits($bankAccount, $exclusionIbans, $command);
            $this->assignTransactionSplitRule($bankAccount, $exclusionIbans);
            return;
        }

        $queryType = select(
            __('cli.transaction.list.query_type.title'),
            [
                'manual' => __('cli.transaction.list.query_type.options.manual'),
                'finQuery' => __('cli.transaction.list.query_type.options.fin_query')
            ]
        );

        $transactions = $this->buildAssignmentQuery($bankAccount, $exclusionIbans);

        if ($this->targetType === RuleTargetType::TRANSACTION) {
            $transactions->leftJoin('rule_transaction', 'transactions.id', '=', 'rule_transaction.transaction_id');
        } else {
            $transactions->leftJoin(
                'rule_transaction_split',
                'transaction_split.id',
                '=',
                'rule_transaction_split.transaction_split_id'
            );
        }

        $viewConfig = TransactionList::$compactView;
        $viewConfigColumns = array_keys($viewConfig);
        if ($this->targetType === RuleTargetType::TRANSACTION_SPLIT) {
            $viewConfigColumns = array_map(
                fn (string $column): string => 'transactions.' . $column . ' as ' . $column,
                $viewConfigColumns
            );
        }
        array_push(
            $viewConfigColumns,
            $this->targetType === RuleTargetType::TRANSACTION
                ? 'rule_transaction.rule_id'
                : 'rule_transaction_split.rule_id'
        );
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
            $bankAccount->id,
            $this->targetType
        );

        if (
            $this->unmatchedTransactionsService->getTotalUnmatchedTransactions(
                $bankAccount,
                $exclusionIbans
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

    private function assignTransactionSplitRule(BankAccount $bankAccount, Collection $exclusionIbans): void
    {
        do {
            $splitId = (int) $this->viewInput(
                __('cli.rule.assign.transaction_split_id'),
                'required|integer|min:1'
            );
            $split = TransactionSplit::query()
                ->whereKey($splitId)
                ->where('type', TransactionSplitType::OTHER->value)
                ->whereHas('transaction', function (Builder $query) use ($bankAccount, $exclusionIbans): void {
                    $query->where('bank_account_iban', $bankAccount->iban);
                    if ($exclusionIbans->isNotEmpty()) {
                        $query->whereNotIn('creditor_iban', $exclusionIbans->toArray());
                    }
                })
                ->first();

            if ($split === null) {
                $this->error(__('cli.rule.assign.transaction_split_not_found', ['id' => $splitId]));
                continue;
            }

            if (DB::table('rule_transaction_split')->where('transaction_split_id', $split->id)->exists()) {
                $this->error(__('cli.rule.assign.transaction_split_already_assigned', ['id' => $splitId]));
                continue;
            }
            break;
        } while (true);

        $cashflow = Cashflow::where('bank_account_id', $bankAccount->id)->first();
        if (!$cashflow instanceof Cashflow) {
            $this->error(__('cli.category.base.error.not_found_cashflow', ['bankAccountId' => $bankAccount->id]));
            return;
        }

        $expectedCashflow = $split->getCashflow();
        $this->treeViewConsoleService->displayCashflowTrees($cashflow);
        $this->manageConsoleService->manage($bankAccount->id, __('cli.rule.assign.cat_mgmt_continue_button_text'));
        $selectedCategoryId = $this->manageConsoleService->findAndSelectCategory($cashflow);
        $selectedCategory = \de\xovatec\financeAnalyzer\Models\Category::findOrFail($selectedCategoryId);
        if ($selectedCategory->getCashflowType() !== $expectedCashflow) {
            $this->error(__('cli.rule.assign.invalid_category_cashflow'));
            return;
        }

        $conditionList = (new ConditionList())->add(
            new Condition(new IdField(), new Equal(), (string) $split->transaction_id)
        );
        $this->saveRule(
            $this->viewInput('Name der Regel', 'required|min:1|unique:rule,name'),
            $selectedCategoryId,
            $conditionList,
            $bankAccount->id,
            RuleTargetType::TRANSACTION_SPLIT,
            [$split->id]
        );
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
        int $bankAccountId,
        RuleTargetType $targetType,
        ?array $targetIds = null
    ): void {
        try {
            DB::beginTransaction();
            $id = $this->ruleDataManager->saveRuleForTarget(
                $name,
                $categoryId,
                $conditionList,
                $bankAccountId,
                $targetType,
                $targetIds
            );
            DB::commit();
            $this->info(__('cli.rule.assign.result.info', ['id' => $id]));
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->error(__('cli.rule.assign.result.error'));
        }
    }

    private function buildAssignmentQuery(BankAccount $bankAccount, Collection $exclusionIbans): Builder
        {
            if ($this->targetType === RuleTargetType::TRANSACTION) {
                $query = Transactions::where('transactions.bank_account_iban', $bankAccount->iban)
                    ->orderBy('transactions.id');
            } else {
                $query = TransactionSplit::query()
                    ->join('transactions', 'transaction_split.transaction_id', '=', 'transactions.id')
                    ->where('transactions.bank_account_iban', $bankAccount->iban)
                    ->where('transaction_split.type', TransactionSplitType::OTHER->value)
                    ->whereNull('transaction_split.deleted_at')
                    ->orderBy('transaction_split.id');
            }

            if ($exclusionIbans->isNotEmpty()) {
                $query->whereNotIn('transactions.creditor_iban', $exclusionIbans->toArray());
            }

            return $query;
        }

    private function viewUnmatchedTransactionSplits(
            BankAccount $bankAccount,
            Collection $exclusionIbans,
            FinCommand $command
        ): void {
            $splitView = [
                'split_id' => ['width' => 8],
                'transaction_id' => ['width' => 13],
                'transaction_date' => ['width' => 10],
                'amount' => ['width' => 10],
                'transaction_type' => ['width' => 35],
                'beneficiary_payee' => ['width' => '35%'],
                'reason_for_payment' => ['width' => '35%'],
                'note' => ['width' => 20],
            ];
            $splits = $this->buildAssignmentQuery($bankAccount, $exclusionIbans)
                ->leftJoin(
                    'rule_transaction_split',
                    'transaction_split.id',
                    '=',
                    'rule_transaction_split.transaction_split_id'
                )
                ->whereNull('rule_transaction_split.transaction_split_id')
                ->select(
                        'transaction_split.id as split_id',
                        'transactions.id as transaction_id',
                        'transactions.transaction_date',
                        'transaction_split.amount',
                        'transactions.transaction_type',
                        'transactions.beneficiary_payee',
                        'transactions.reason_for_payment',
                        'transaction_split.note'
                    )
                    ->limit(10)
                    ->get();

            if ($splits->isNotEmpty()) {
                $this->tableConsolePagination(
                    $splits,
                    $splitView,
                    null,
                    'cli.rule.assign.transaction_split.table.header.'
                );
            }
    }

    /**
     *
     * @param BankAccount $bankAccount
     * @param Collection $exclusionIbans
     * @param FinCommand $command
     * @return void
     */
    private function viewUnmatchedTransactions(
        BankAccount $bankAccount,
        Collection $exclusionIbans,
        FinCommand $command
    ): void {
        $viewConfig = TransactionList::$compactView;
        $total = null;
        $cursor = null;

        do {
            $unmatchedTransactions = $this->buildBaseUnmatchedTransactionsQuery($bankAccount, $exclusionIbans);

            if ($total === null) {
                $this->unmatchedTransactionsDisplayService->displayUnmatchedTransactionsOverview(
                    $this->unmatchedTransactionsService->getUnmatchedTransactions(
                        CopyBuilderQueryHelper::copy($unmatchedTransactions)
                    ),
                );
            }

            if (strlen($command->option('range')) > 0) {
                $unmatchedTransactions = FilterTransactionDurationHelper::applyFilter(
                    $unmatchedTransactions,
                    $command->option('range'),
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
     * @param Collection $exclusionIbans
     * @return Builder
     */
    private function buildBaseUnmatchedTransactionsQuery(BankAccount $bankAccount, Collection $exclusionIbans): Builder
    {
        $transactions = Transactions::where('bank_account_iban', $bankAccount->iban)->orderBy('id');

        if ($exclusionIbans->isNotEmpty()) {
            $transactions = $transactions->whereNotIn('creditor_iban', $exclusionIbans->toArray());
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
        $pivot = $this->targetType === RuleTargetType::TRANSACTION
            ? 'rule_transaction.rule_id'
            : 'rule_transaction_split.rule_id';
        $clonedTransactions->whereNotNull($pivot);
        $found = $clonedTransactions->count();
        $this->overlapMatches = $found > 0;
        if ($found > 0) {
            $this->emptyLn();
            $this->alert(__('cli.rule.assign.found_already_matched', ['count' => $found]));
            $this->halt();
        }
    }
}
