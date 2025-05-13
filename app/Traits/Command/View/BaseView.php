<?php

namespace de\xovatec\financeAnalyzer\Traits\Command\View;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Concerns\InteractsWithIO;

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
}
