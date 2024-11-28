<?php

namespace de\xovatec\financeAnalyzer\Enums;

enum TransactionCheckCode: int
{
    case NONE = 0;
    case DETECTED_CASH_PAYMENT = 1;
}
