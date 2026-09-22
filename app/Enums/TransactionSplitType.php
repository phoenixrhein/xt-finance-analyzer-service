<?php

namespace de\xovatec\financeAnalyzer\Enums;

enum TransactionSplitType: string
{
    case CASH_PAYOUT = 'cash_payout';
    case OTHER = 'other';
}
