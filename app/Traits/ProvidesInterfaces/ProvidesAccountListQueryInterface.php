<?php

namespace de\xovatec\financeAnalyzer\Traits\ProvidesInterfaces;

use de\xovatec\financeAnalyzer\Services\Query\AccountListQuery;

interface ProvidesAccountListQueryInterface
{
    /**
     *
     * @return AccountListQuery
     */
    public function getAccountListQuery(): AccountListQuery;
}
