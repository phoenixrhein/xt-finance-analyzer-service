<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use de\xovatec\financeAnalyzer\Models\BankAccount;

use function Laravel\Prompts\confirm;

class AccountEdit extends AbstractAccountEdit
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:account-edit {accountId : [:cli.account.base.param.account_id:]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.account.edit.description';

    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $accountId = (int)$this->argument('accountId');
        $bankAccount = BankAccount::find($accountId);

        if (!$bankAccount instanceof BankAccount) {
            $this->emptyLn();
            $this->error(__('cli.account.base.error.not_found_account_id', ['accountId' => $accountId]));
            return;
        }

        $iban = $this->viewIbanInput($bankAccount->iban);
        $bic = $this->viewBicInput($bankAccount->bic);

        if (!$this->confirmPrompt(__('cli.base.confirm_save'))) {
            return;
        }

        $bankAccount->iban = $iban;
        $bankAccount->bic = $bic;
        $bankAccount->save();
        $this->info(__('cli.account.edit.edited', ['accountId' => $bankAccount->id]));
    }
}
