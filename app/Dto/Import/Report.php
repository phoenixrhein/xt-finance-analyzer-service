<?php

namespace de\xovatec\financeAnalyzer\Dto\Import;

class Report
{
    /**
     *
     * @var integer
     */
    private int $imported = 0;

    /**
     *
     * @var integer
     */
    private int $duplicates = 0;

    /**
     *
     * @return void
     */
    public function incrementImported(): void
    {
        $this->imported++;
    }

    /**
     *
     * @return void
     */
    public function incrementDuplicates(): void
    {
        $this->duplicates++;
    }

    /**
     *
     * @return integer
     */
    public function getImported(): int
    {
        return $this->imported;
    }

    /**
     *
     * @return integer
     */
    public function getDuplicates(): int
    {
        return $this->duplicates;
    }
}
