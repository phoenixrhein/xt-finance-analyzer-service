<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Support\Str;
use de\xovatec\financeAnalyzer\Models\BankAccount;

use function Laravel\Prompts\text;

trait IbanInput
{
    /**
     *
     * @param string $rawIban
     * @return string
     */
    protected function viewIbanInput(string $rawIban = '', $uniqueInBankAccount = true): string
    {
        $iban = $rawIban;
        do {
            $iban = text(
                label: __('cli.view.input.iban'),
                default: $iban
            );

            $valid = $this->viewValidatorError(
                [
                    'iban' => $iban
                ],
                ['iban' => BankAccount::getRules()['iban']]
            );

            if (
                $uniqueInBankAccount
                && Str::length($rawIban) == 0
                && $valid
                && BankAccount::where('iban', $iban)->count()
            ) {
                $valid = false;
                $this->error(__('cli.view.validate_error.duplicate_iban'));
            }
        } while (!$valid);

        return $iban;
    }
}
