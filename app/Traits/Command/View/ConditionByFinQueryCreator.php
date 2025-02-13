<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use de\xovatec\financeAnalyzer\Dto\FinQuery\ConditionList;
use Illuminate\Database\Eloquent\Collection;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\FinQuery\FinQueryBuilder;
use de\xovatec\financeAnalyzer\Services\RuleToConditionTransformer;
use de\xovatec\financeAnalyzer\Services\Expression\CliErrorHighlighter;
use de\xovatec\financeAnalyzer\Services\Expression\ExpressionSyntaxParser;
use de\xovatec\financeAnalyzer\Traits\Command\DisplayInterimTransactionResult;

trait ConditionByFinQueryCreator
{
    use SimpleInput;
    use DisplayInterimTransactionResult;

    /**
     *
     * @return ExpressionSyntaxParser
     */
    abstract private function getExpressionSyntaxParser(): ExpressionSyntaxParser;

    /**
     *
     * @return CliErrorHighlighter
     */
    abstract private function getCliErrorHighlighter(): CliErrorHighlighter;

    /**
     *
     * @return RuleToConditionTransformer
     */
    abstract private function getTransformer(): RuleToConditionTransformer;

    /**
     * @return FinQueryBuilder
     */
    abstract private function getFinQueryBuilder(): FinQueryBuilder;

    /**
     *
     * @param BankAccount $bankAccount
     * @param Collection|null $ignoreIbans
     * @return ConditionList
     */
    private function viewConditionByFinQueryCreator(
        BankAccount $bankAccount,
        ?Collection $ignoreIbans = null
    ): ConditionList {
        do {
            $conditions = $this->inputFinQuery(
                $this->getFinQueryBuilder()->build($conditionList ?? new ConditionList())
            );
            $conditionList = $this->getTransformer()->transform($conditions);
            $this->displayInterimResult(
                $bankAccount,
                $conditionList,
                $ignoreIbans
            );
        } while (!$this->confirmPrompt(__('cli.view.fin_query_creator.confirm_condition')));

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
