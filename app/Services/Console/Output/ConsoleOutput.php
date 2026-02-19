<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Output;

use de\xovatec\financeAnalyzer\Traits\Command\View\InitViewIO;
use de\xovatec\financeAnalyzer\Traits\Command\View\InteractsWithIOExtended;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleOutput implements ConsoleOutputInterface
{
    use InteractsWithIOExtended;
    use InitViewIO;
    
    /**
     *
     * @param SymfonyStyle $io
     */
    public function __construct(private SymfonyStyle $io)
    {
        $this->initViewIO();
    }

    /**
     * Outputs a line of text with an optional style.
     *
     * @param string $message The message to output.
     * @param string|null $style Optional style for the message (e.g., 'info', 'error').
     * @return void
     */
    public function line(string $message, ?string $style = null): void
    {
        $styled = $style ? "<$style>$message</$style>" : $message;
        $this->io->writeln($styled, OutputInterface::VERBOSITY_NORMAL);
    }

    /**
     * Outputs an informational message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function info(string $message): void
    {
        $this->line($message, 'info');
    }

    /**
     * Outputs an error message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function error(string $message): void
    {
        $this->line($message, 'error');
    }

    /**
     * Outputs a question message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function question(string $message): void
    {
        $this->line($message, 'question');
    }

    /**
     * Outputs a warning message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function warn(string $message): void
    {
        if (! $this->io->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');

            $this->io->getFormatter()->setStyle('warning', $style);
        }
        $this->line($message, 'warning');
    }

    /**
     * Outputs a comment message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function comment(string $message): void
    {
        $this->line($message, 'comment');
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
