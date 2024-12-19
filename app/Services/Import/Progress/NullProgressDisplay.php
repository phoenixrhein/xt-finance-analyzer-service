<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Progress;

class NullProgressDisplay implements ProgressDisplayInterface
{
    /**
     *
     * @return void
     */
    public function show(): void
    {
        // no action required
    }

    /**
     *
     * @return void
     */
    public function reset(): void
    {
        // no action required
    }
}
