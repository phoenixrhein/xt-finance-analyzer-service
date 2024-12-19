<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Progress;

use de\xovatec\financeAnalyzer\Traits\Command\View\SimpleInput;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleProgressDisplay implements ProgressDisplayInterface
{
    use SimpleInput;

    /**
     *
     * @var array
     */
    private array $loadString = ['\\', '-', '/', '-'];

    /**
     *
     * @var integer
     */
    private int $index = 0;

    /**
     *
     * @var integer
     */
    private int $counter = 0;

    /**
     *
     * @var integer
     */
    private int $changeAfter = 8; // Iterationen, nach denen gewechselt wird

    /**
     *
     * @param SymfonyStyle $io
     */
    public function __construct(private SymfonyStyle $io)
    {
    }

    /**
     *
     * @return void
     */
    public function show(): void
    {
        $this->io->write(
            sprintf('<fg=green>> %s... </>', $this->loadString[$this->index]),
        );

        $this->counter++;
        if ($this->counter === $this->changeAfter) {
            $this->index = ($this->index + 1) % count($this->loadString);
            $this->counter = 0;
        }

        $this->io->write("\r");
    }

    /**
     *
     * @return void
     */
    public function reset(): void
    {
        $this->io->write("           \r");
    }
}


