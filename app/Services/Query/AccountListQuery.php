<?php

namespace de\xovatec\financeAnalyzer\Services\Query;

use Illuminate\Support\Facades\DB;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use Illuminate\Database\Eloquent\Builder;

class AccountListQuery
{
    /**
     *
     * @return Builder
     */
    public function createList(): Builder
    {
        return BankAccount::leftJoin(
            'bank_account_user',
            'bank_account.id',
            '=',
            'bank_account_user.bank_account_id'
        )
        ->select(
            'bank_account.id',
            'bank_account.iban',
            'bank_account.bic',
            DB::raw('COUNT(bank_account_user.user_id) as accounts_count')
        )
        ->groupBy('bank_account.id', 'bank_account.iban', 'bank_account.bic');
    }
}
