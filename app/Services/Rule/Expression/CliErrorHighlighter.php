<?php

namespace de\xovatec\financeAnalyzer\Services\Rule\Expression;

use Symfony\Component\Console\Output\OutputInterface;
use de\xovatec\financeAnalyzer\Dto\FinQuery\Parser\ErrorReport;

class CliErrorHighlighter
{
    /**
     *
     * @var OutputInterface
     */
    private OutputInterface $output;

    /**
     *
     * @param string $expression
     * @param ErrorReport $errorReport
     * @param OutputInterface $output
     * @return void
     */
    public function printErrors(string $expression, ErrorReport $errorReport, OutputInterface $output): void
    {
        $this->output = $output;

        if (!$errorReport->hasErrors()) {
            $this->output->writeln("<info>" . __('cli.service.cli_error_highlighter.no_error_found') . "</info>");
            return;
        }

        $highlightedExpression = $this->highlightErrors($expression, $errorReport->getErrors());
        $this->output->writeln("\n<error>" . __('cli.service.cli_error_highlighter.error_report_title') . ":</error>");
        $this->output->writeln($highlightedExpression);
        $this->output->writeln("\n<fg=yellow>" . __('cli.service.cli_error_highlighter.details') . ":</>");

        foreach ($errorReport->getErrors() as $error) {
            $this->output->writeln(
                sprintf(
                    "<fg=red>[%s]</> Pos: %d-%d → %s",
                    $error->getType()->name,
                    $error->getPosition()->getFrom() + 1, //for php first pos. is 0, so add 1
                    $error->getPosition()->getTo() + 1,
                    $error->getMessage()
                )
            );
        }
    }

    /**
     *
     * @param array $errors
     * @return array
     */
    private function preparePostions(array $errors): array
    {
        $positions = [];

        // Collect error areas and merge overlapping areas
        foreach ($errors as $error) {
            $start = $error->getPosition()->getFrom();
            $end = $error->getPosition()->getTo();

            $merged = false;

            // If the new area overlaps with an existing one, merge it
            foreach ($positions as $index => $pos) {
                if ($start <= $positions[$index]['end'] && $end >= $positions[$index]['start']) {
                    $positions[$index]['start'] = min($positions[$index]['start'], $start);
                    $positions[$index]['end'] = max($positions[$index]['end'], $end);
                    $merged = true;
                    break;
                }
            }

            if (!$merged) {
                $positions[] = ['start' => $start, 'end' => $end];
            }
        }

        // Sort by starting position
        usort($positions, fn($a, $b) => $a['start'] <=> $b['start']);

        return $positions;
    }

    /**
     *
     * @param string $expression
     * @param array $errors
     * @return string
     */
    private function highlightErrors(string $expression, array $errors): string
    {
        $highlighted = '';
        $lastPos = 0;
        $errorLine = str_repeat(' ', strlen($expression)); // Line for '^' markers

        foreach ($this->preparePostions($errors) as $pos) {
            $highlighted .= substr($expression, $lastPos, $pos['start'] - $lastPos);

            $errorPart = substr($expression, $pos['start'], $pos['end'] - $pos['start']);

            if ($this->output->isDecorated()) {
                $highlighted .= "<fg=red>{$errorPart}</>";
            } else {
                $highlighted .= $errorPart;

                // Set error line with "^"
                for ($i = $pos['start']; $i < $pos['end']; $i++) {
                    $errorLine[$i] = '^';
                }
            }

            $lastPos = $pos['end'];
        }

        $highlighted .= substr($expression, $lastPos);

        if (!$this->output->isDecorated()) {
            return $highlighted . PHP_EOL . $errorLine;
        }

        return $highlighted;
    }
}
