<?php

namespace de\xovatec\financeAnalyzer\Enums;

use de\xovatec\financeAnalyzer\Traits\Utils\EnumFromName;

enum TimespanType
{
    use EnumFromName;

    case year;
    case month;
}
