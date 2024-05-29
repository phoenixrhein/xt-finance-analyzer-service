<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use function Laravel\Prompts\confirm;

use de\xovatec\financeAnalyzer\Models\Cashflow;
use de\xovatec\financeAnalyzer\Models\BankAccount;

class AccountAdd extends AbstractAccountEdit
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fin:account-add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cli.account.add.description';


    /**
     * @inheritDoc
     */
    protected function process(): void
    {
        $iban = $this->viewIbanInput();
        $bic = $this->viewBicInput();

        if (
            !confirm(
                label: __('cli.account.add.confirm'),
                yes: __('cli.base.button.create'),
                no: __('cli.base.button.abort')
            )
        ) {
            return;
        }

        $newEntry = BankAccount::create(['iban' => $iban, 'bic' => $bic]);

        Cashflow::createWithCategories(['bank_account_id' => $newEntry->id]);
        $this->info(__('cli.account.add.created', ['iban' => $iban, 'id' => $newEntry->id]));
    }
}
