<?php

namespace de\xovatec\financeAnalyzer\Exceptions;

use Throwable;
use Carbon\Carbon;
use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use de\xovatec\financeAnalyzer\Helpers\ExceptionMessageHelper;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use NunoMaduro\Collision\Adapters\Laravel\ExceptionHandler as CollisionHandler;
use Symfony\Component\Console\Exception\ExceptionInterface as SymfonyConsoleExceptionInterface;

class Handler extends ExceptionHandler
{
    /**
     *
     * @var CollisionHandler
     */
    protected CollisionHandler $collisionHandler;

    /**
     * constructor
     */
    public function __construct()
    {
        parent::__construct(app());
        if (class_exists(\NunoMaduro\Collision\Adapters\Laravel\ExceptionHandler::class)) {
            $this->collisionHandler = new \NunoMaduro\Collision\Adapters\Laravel\ExceptionHandler(app(), $this);
        }
    }

    /**
     *
     * @param Throwable $e
     * @return void
     */
    public function report(Throwable $e): void
    {
        //do not log separat. Logging part of renderForConsole(), because there will be generated a msg log id
    }

    /**
     *
     * @param mixed $output
     * @param Throwable $exception
     * @return void
     */
    public function renderForConsole($output, Throwable $exception): void
    {
        $msg = ExceptionMessageHelper::parse($exception->getMessage());
        if ($msg->key !== null) {
            $output->writeln(' <error> ' . __(Str::replace('msgctx.', 'cli.', $msg->key), $msg->json) . ' </error>');
            $output->writeln('');
            if ($msg->key !== null) {
                $logMsgId = 'FIN-' . Carbon::now()->format('YmdHisv');
                Log::error(
                    'Error with log-message-id: ' . $logMsgId . PHP_EOL .
                    ExceptionMessageHelper::cleanException($exception)
                );
            }
            return;
        }

        if (!config('app.debug')) {
            $output->writeln('');
            $logMsgId = 'FIN-' . Carbon::now()->format('YmdHisv');
            $output->writeln(' <error> ' . __('cli.base.error.message', ['msgId' => $logMsgId]) . ' </error>');
            $output->writeln('');
            Log::error('Error with log-message-id: ' . $logMsgId . PHP_EOL . $exception);
        } else {
            if ($exception instanceof SymfonyConsoleExceptionInterface) {
                parent::renderForConsole($output, $exception);
            } elseif (isset($this->collisionHandler)) {
                $this->collisionHandler->renderForConsole($output, $exception);
            } else {
                parent::renderForConsole($output, $exception);
            }
        }
    }

    /**
     *
     * @param mixed $method
     * @param mixed $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (isset($this->collisionHandler) && method_exists($this->collisionHandler, $method)) {
            return call_user_func_array([$this->collisionHandler, $method], $parameters);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }
}
