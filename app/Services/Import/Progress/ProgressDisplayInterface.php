<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Progress;

interface ProgressDisplayInterface
{
    /**
     *
     * @return void
     */
    public function show(): void;

    /**
     *
     * @return void
     */
    public function reset(): void;
}
