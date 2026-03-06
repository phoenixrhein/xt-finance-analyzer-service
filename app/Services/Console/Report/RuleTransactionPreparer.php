<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Report;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Services\Console\AbstractIOService;
use de\xovatec\financeAnalyzer\Services\Rule\RefreshTransactionRuleIndexService;
use de\xovatec\financeAnalyzer\Services\Rule\RuleTransactionAssignmentsValidator;

class RuleTransactionPreparer extends AbstractIOService
{
    /**
     *
     * @param RefreshTransactionRuleIndexService $refreshIndexService
     * @param RuleTransactionAssignmentsValidator $validator
     */
    public function __construct(
        private RefreshTransactionRuleIndexService $refreshIndexService,
        private RuleTransactionAssignmentsValidator $validator
    ) {
        parent::__construct();
    }

    /**
     *
     * @param BankAccount $bankAccount
     * @param boolean $considerIgnoreIbans
     * @return void
     */
    public function prepareForReport(BankAccount $bankAccount, bool $considerIgnoreIbans): void
    {
        $this->validator->validateAll($bankAccount, $considerIgnoreIbans);
        $this->separatorLine();
        $this->refreshIndexService->refreshAll($bankAccount, $considerIgnoreIbans);
        $this->separatorLine();
    }
}
