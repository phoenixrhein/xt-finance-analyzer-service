<?php

namespace de\xovatec\financeAnalyzer\Services\Console\Output;

class NullOutput implements ConsoleOutputInterface
{
    /**
     * Outputs a line of text with an optional style.
     *
     * @param string $message The message to output.
     * @param string|null $style Optional style for the message (e.g., 'info', 'error').
     * @return void
     */
    public function line(string $message, ?string $style = null): void
    {
        // No output
    }

    /**
     * Outputs an informational message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function info(string $message): void
    {
        // No output
    }

    /**
     * Outputs an error message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function error(string $message): void
    {
        // No output
    }

    /**
     * Outputs a question message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function question(string $message): void
    {
        // No output
    }

    /**
     * Outputs a warning message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function warn(string $message): void
    {
        // No output
    }

    /**
     * Outputs a comment message.
     *
     * @param string $message The message to output.
     * @return void
     */
    public function comment(string $message): void
    {
        // No output
    }

    /**
     * Write an empty line
     *
     * @return void
     */
    public function emptyLn(): void
    {
        // No output
    }
}
