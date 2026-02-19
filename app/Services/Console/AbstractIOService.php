<?php

namespace de\xovatec\financeAnalyzer\Services\Console;

use de\xovatec\financeAnalyzer\Traits\Command\View\BaseView;
use de\xovatec\financeAnalyzer\Traits\Command\View\InitViewIO;
use de\xovatec\financeAnalyzer\Traits\Command\View\InteractsWithIOExtended;

abstract class AbstractIOService
{
    use InteractsWithIOExtended;
    use BaseView;
    use InitViewIO;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initViewIO();
    }
}
