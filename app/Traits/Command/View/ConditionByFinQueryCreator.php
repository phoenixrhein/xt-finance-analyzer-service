<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Database\Eloquent\Builder;
use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use de\xovatec\financeAnalyzer\Helpers\CopyBuilderQueryHelper;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Services\Expression\CliErrorHighlighter;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionSyntaxParser;
use de\xovatec\financeAnalyzer\Traits\Command\DisplayInterimTransactionResult;

use function Laravel\Prompts\select;

trait ConditionByFinQueryCreator
{
    use SimpleInput;
    use DisplayInterimTransactionResult;

    /**
     *
     * @return ExpressionSyntaxParser
     */
    abstract protected function getExpressionSyntaxParser(): ExpressionSyntaxParser;

    /**
     *
     * @return CliErrorHighlighter
     */
    abstract protected function getCliErrorHighlighter(): CliErrorHighlighter;

    /**
     *
     * @return RuleToConditionTransformer
     */
    abstract protected function getTransformer(): RuleToConditionTransformer;

    /**
     * @return FinQueryBuilder
     */
    abstract protected function getFinQueryBuilder(): FinQueryBuilder;

    /**
     *
     * @param Builder $transactions
     * @return ConditionList
     */
    private function viewConditionByFinQueryCreator(Builder $transactions): ConditionList
    {
        $confirmation = null;
        $start = 0;
        do {
            if ($confirmation !== 'more') {
                $conditions = $this->inputFinQuery(
                    $this->getFinQueryBuilder()->build($conditionList ?? new ConditionList())
                );
                $conditionList = $this->getTransformer()->transform($conditions);
            }
            $hasMore = $this->displayInterimResult(
                CopyBuilderQueryHelper::copy($transactions),
                $conditionList,
                $start
            );

            $confirmOptions = [
                'yes' =>  __('cli.base.button.yes'),
                'no' =>  __('cli.base.button.no')
            ];

            if ($hasMore) {
                $confirmOptions = Arr::prepend(
                    $confirmOptions,
                    __('cli.view.condition_creator.option_more_data'),
                    'more'
                );
            }

            $confirmation = select(
                __('cli.view.condition_creator.confirm_condition'),
                $confirmOptions
            );

            if ($confirmation === 'more') {
                $start += $this->getDisplayLimit();
            } elseif ($confirmation === 'no') {
                $start = 0;
            }

        } while ($confirmation !== 'yes');

        return $conditionList;
    }

    /**
     *
     * @param string $expression
     * @return array
     */
    private function inputFinQuery(string $expression): array
    {
        do {
            $isValid = true;

            $expression = $this->viewInput(
                __('cli.view.fin_query_creator.input'),
                'required',
                $expression
            );

            $conditions = $this->getExpressionSyntaxParser()->parse($expression);

            if ($this->getExpressionSyntaxParser()->getErrorReport()->hasErrors()) {
                $this->getCliErrorHighlighter()->printErrors(
                    $expression,
                    $this->getExpressionSyntaxParser()->getErrorReport(),
                    $this->output
                );
                $isValid = false;
            }
        } while ($isValid === false);

        return $conditions;
    }
}
