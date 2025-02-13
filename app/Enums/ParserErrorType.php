<?php

namespace de\xovatec\financeAnalyzer\Enums;

enum ParserErrorType
{
    case FIELD;
    case OPERATOR;
    case VALUE;
    case SYNTAX;
    case LOGICAL_OPERATOR;
}
