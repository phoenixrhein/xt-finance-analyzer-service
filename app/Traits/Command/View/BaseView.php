<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Concerns\InteractsWithIO;

use function Laravel\Prompts\confirm;

trait BaseView
{
    use InteractsWithIO;

    /**
     *
     * @param array $data
     * @param array $rules
     * @return boolean
     */
    public function viewValidatorError(array $data, array $rules): bool
    {
        $validator = Validator::make(
            $data,
            Arr::only($rules, array_keys($data))
        );

        $valid = true;
        if ($validator->fails()) {
            $valid = false;
            $this->error($validator->errors()->first());
        }

        return $valid;
    }

    /**
     * Write an empty line
     *
     * @return void
     */
    public function emptyLn(): void
    {
        $this->line('');
    }

    /**
     *
     * @param string $label
     * @param boolean $default
     * @param string $yes
     * @param string $no
     * @param boolean $required
     * @param mixed $validate
     * @param string $hint
     * @return boolean
     */
    public function confirmPrompt(
        string $label,
        bool $default = true,
        string $yes = '',
        string $no = '',
        bool|string $required = false,
        mixed $validate = null,
        string $hint = ''
    ): bool {
        return confirm(
            $label,
            $default,
            Str::length($yes) ? $yes : __('cli.base.button.yes'),
            Str::length($no) ? $no : __('cli.base.button.no'),
            $required,
            $validate,
            $hint
        );
    }    
}
