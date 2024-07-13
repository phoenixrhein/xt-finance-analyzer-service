<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;
use de\xovatec\financeAnalyzer\Traits\Command\View\IbanInput;

use function Laravel\Prompts\text;

abstract class AbstractAccountEdit extends FinCommand
{
    use IbanInput;
    
    /**
     *
     * @param string $rawBic
     * @return string
     */
    protected function viewBicInput(string $rawBic = ''): string
    {
        $bic = $rawBic;
        do {
            $bic = text(
                label: __('cli.account.add.input_bic'),
                default: $bic
            );

            $valid = $this->viewValidatorError(
                [
                    'bic' => $bic
                ],
                BankAccount::getRules()
            );
        } while (!$valid);

        return $bic;
    }
}
