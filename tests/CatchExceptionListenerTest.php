<?php

namespace Satori\CatchException\Tests;

use PHPUnit\Framework\TestCase;
use Satori\CatchException\Lib\CatchExceptionListener;
use Satori\CatchException\Lib\Exception\AbstractException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class CatchExceptionListenerTest extends TestCase
{
    public function testOnKernelException()
    {
        $listener = new CatchExceptionListener();
        $event = static::createMock(GetResponseForExceptionEvent::class);
        $response = static::createMock(JsonResponse::class);
        $exception = static::createMock(AbstractException::class);
        $exception->expects(static::once())->method('getResponse')->willReturn($response);

        $event->expects(static::once())->method('getException')->willReturn($exception);
        $event->expects(static::once())->method('setResponse');

        $listener->onKernelException($event);
    }
}