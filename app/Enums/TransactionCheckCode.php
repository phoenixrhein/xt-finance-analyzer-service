<?php

namespace de\xovatec\financeAnalyzer\Enums;

enum TransactionCheckCode: int
{
    case NONE = 0;
    case DETECTED_CASH_PAYMENT = 1;
    case UNCATEGORISABLE = 2;
    // next bits which can be used 4, 8, 16, 32, 64, 128 etc.
}
