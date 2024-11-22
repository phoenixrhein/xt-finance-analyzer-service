<?php

namespace de\xovatec\financeAnalyzer\Exceptions;

use Throwable;
use Carbon\Carbon;
use BadMethodCallException;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use NunoMaduro\Collision\Adapters\Laravel\ExceptionHandler as CollisionHandler;

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
        $this->collisionHandler = new CollisionHandler(app(), $this);
    }

    /**
     *
     * @param mixed $output
     * @param Throwable $exception
     * @return void
     */
    public function renderForConsole($output, Throwable $exception): void
    {
        if (!config('app.debug')) {
            $output->writeln('');
            $logMsgId = 'FIN-' . Carbon::now()->format('YmdHisv');
            $output->writeln(' <error> '.__('cli.base.error.message', ['msgId' => $logMsgId]).' </error>');
            $output->writeln('');
            Log::error('Error with log-message-id: ' . $logMsgId . PHP_EOL .$exception);
        } else {
            $this->collisionHandler->renderForConsole($output, $exception);
        }
    }

    /**
     *
     * @param mixed $method
     * @param mixed $parameters
     * @return void
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->collisionHandler, $method)) {
            return call_user_func_array([$this->collisionHandler, $method], $parameters);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }
}
