<?php

namespace de\xovatec\financeAnalyzer\Enums;

enum RuleTargetType: string
{
    case TRANSACTION = 'transaction';
    case TRANSACTION_SPLIT = 'transaction_split';
}
