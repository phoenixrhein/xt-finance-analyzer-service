<?php

namespace de\xovatec\financeAnalyzer\Console\Commands\Account;

use function Laravel\Prompts\text;

use Illuminate\Support\Str;
use de\xovatec\financeAnalyzer\Models\BankAccount;
use de\xovatec\financeAnalyzer\Console\Commands\FinCommand;

abstract class AbstractAccountEdit extends FinCommand
{
    /**
     *
     * @param string $rawIban
     * @return string
     */
    protected function viewIbanInput(string $rawIban = ''): string
    {
        $iban = $rawIban;
        do {
            $iban = text(
                label: __('cli.account.add.input_iban'),
                default: $iban
            );

            $valid = $this->viewValidatorError(
                [
                    'iban' => $iban
                ],
                ['iban' => BankAccount::getRules()['iban']]
            );

            if (Str::length($rawIban) == 0 && $valid && BankAccount::where('iban', $iban)->count()) {
                $valid = false;
                $this->error(__('cli.account.add.validate_error.duplicate_iban'));
            }
        } while (!$valid);

        return $iban;
    }

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