<?php

namespace Satori\CatchException\Lib;

use Satori\CatchException\Lib\Exception\AbstractException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Class CatchExceptionListener
 *
 * @package Satori\CatchException\Lib
 */
class CatchExceptionListener
{
    /**
     * Handle event
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if ($exception instanceof AbstractException) {
            $event->setResponse($exception->getResponse());
        }
    }
}