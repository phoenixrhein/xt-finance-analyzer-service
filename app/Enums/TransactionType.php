<?php

namespace de\xovatec\financeAnalyzer\Enums;

enum TransactionType: string
{
    case CARD_PAYMENT_WITH_CASH_PAYMENT = 'KARTENZAHLUNG MIT BARAUSZAHLUNG';
}
