<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use de\xovatec\financeAnalyzer\Models\Transactions;
use de\xovatec\financeAnalyzer\Rules\ValidMonthYear;
use de\xovatec\financeAnalyzer\Console\Commands\Transaction\TransactionList;

use function Laravel\Prompts\search;

trait FindAndSelectTransaction
{
    use SimpleInput;
    use TableConsolePagination;

    /**
     *
     * @param string $iban
     * @return integer
     */
    protected function viewTransactionId(string $iban): int
    {
        $month = '';
        do {
            $month = $this->viewInput(
                __('cli.view.find_and_select_transaction.month_input'),
                ['required', new ValidMonthYear],
                $month
            );

            $searchByTextPrefix = 'TEXT:';
            $searchBy = search(
                label: __('cli.view.find_and_select_transaction.search'),
                options: function($value) use($iban, $month, $searchByTextPrefix) {
                    if (strlen($value) < 3) {
                        return [];
                    }

                    $result = Transactions::where("beneficiary_payee", 'like', "%{$value}%")
                        ->where('bank_account_iban', $iban)
                        ->whereRaw(
                            "DATE_FORMAT(transaction_date, '%Y-%m') = ?",
                            [substr($month, 2, 4) . '-' . substr($month, 0, 2)]
                        )
                        ->groupBy('creditor_iban')
                        ->pluck('beneficiary_payee', 'creditor_iban')
                        ->all();
                    return [
                        $searchByTextPrefix . $value => __('cli.view.find_and_select_transaction.search_by_text') . ': ' . $value
                    ] + $result;
                    
                }
            );

            $query = Transactions::select(array_keys(TransactionList::$compactView))
                ->where('bank_account_iban', $iban)
                ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [substr($month, 2, 4) . '-' . substr($month, 0, 2)]);

            if (Str::startsWith($searchBy, $searchByTextPrefix)) {
                $query->where("beneficiary_payee", 'like', '%' . Str::replace($searchByTextPrefix, '', $searchBy) . '%');
            } else {
                $query->where("creditor_iban", $searchBy);
            }

            $this->tableConsolePagination(
                $query->get(),
                TransactionList::$compactView,
                null,
                'cli.transaction.base.table.header.'
            );

            $transactionId = $this->viewInput(
                label: __('cli.view.find_and_select_transaction.transaction_id_input'),
                rules: ['nullable', Rule::in($query->pluck('id')->toArray())],
                hint: __('cli.view.find_and_select_transaction.transaction_id_input_hint')
            );
        } while (!is_numeric($transactionId));

        return (int)$transactionId;
    }
}
