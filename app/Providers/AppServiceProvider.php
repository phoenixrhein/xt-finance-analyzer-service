<?php

namespace de\xovatec\financeAnalyzer\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Style\SymfonyStyle;
use de\xovatec\financeAnalyzer\Services\Import\Progress\NullProgressDisplay;
use de\xovatec\financeAnalyzer\Services\Import\Progress\ConsoleProgressDisplay;
use de\xovatec\financeAnalyzer\Services\Import\Progress\ProgressDisplayInterface;
use de\xovatec\financeAnalyzer\Services\Import\Transaction\Detector\FileTypeDetector;
use de\xovatec\financeAnalyzer\Services\Import\Transaction\Detector\CSVFormatDetector;
use de\xovatec\financeAnalyzer\Services\Import\Transaction\Format\Csv\Camt52V8CsvParser;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \de\xovatec\financeAnalyzer\Exceptions\Handler::class
        );

        $this->app->bind(CSVFormatDetector::class, function () {
            return new CSVFormatDetector(
                [new Camt52V8CsvParser()]
            );
        });

        $this->app->bind(FileTypeDetector::class, function (Application $app) {
            return new FileTypeDetector(
                [$app->make(CSVFormatDetector::class)]
            );
        });

        $this->app->bind(ConsoleProgressDisplay::class, function () {
            return new ConsoleProgressDisplay(
                new SymfonyStyle(
                    new \Symfony\Component\Console\Input\ArgvInput(),
                    new \Symfony\Component\Console\Output\ConsoleOutput()
                )
            );
        });

        $this->app->bind(ProgressDisplayInterface::class, function ($app) {
            if ($app->runningInConsole()) {
                return $app->make(ConsoleProgressDisplay::class);
            }

            return new NullProgressDisplay();
        });
    }
}
