<?php

namespace de\xovatec\financeAnalyzer\Services\FinQuery;

use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\BaseField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\NoteField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\AmountField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\CreditorIbanField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\TransactionTypeField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\BeneficiaryPayeeField;
use de\xovatec\financeAnalyzer\Services\FinQuery\Fields\Transaction\ReasonForPaymentField;

class FieldConfig
{
    /**
     *
     * @return BaseField[]
     */
    public static function getAvailableFields(): array
    {
        return [
            new AmountField(),
            new BeneficiaryPayeeField(),
            new CreditorIbanField(),
            new NoteField(),
            new ReasonForPaymentField(),
            new TransactionTypeField(),
        ];
    }
}
