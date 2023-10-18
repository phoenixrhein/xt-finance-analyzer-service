<?php

namespace de\xovatec\financeAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    const UPDATED_AT = null;

    /**
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bank_account_iban',
        'booking_date',
        'exchange_date',
        'booking_type',
        'reason_for_payment',
        'creditor_id',
        'mandate_ reference',
        'customer_reference',
        'collector_reference',
        'debit_original_amount',
        'reimbursement_of_expenses_return_debit',
        'beneficiary_payee',
        'creditor_iban',
        'creditor_bic',
        'amount',
        'currency',
        'hash_identifier',
        'comment'
    ];
}
