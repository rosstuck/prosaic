<?php

declare(strict_types=1);

namespace Prosaic\Tests;

use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prosaic\HttpDispatcher;
use Prosaic\NoResponseReturned;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function implode;

final class HttpDispatcherTest extends TestCase
{
    public function testNoMiddlewareThrowsException(): void
    {
        $dispatcher = new HttpDispatcher();

        $this->expectException(NoResponseReturned::class);
        $dispatcher->dispatch(new ServerRequest());
    }

    public function testMiddlewareThatDoesNotReturnAnythingTriggersException(): void
    {
        $httpDispatcher = new HttpDispatcher(
            // does nothing but forward the request onwards
            $this->middleware(static fn ($request, $next) => $next->handle($request))
        );

        $this->expectException(NoResponseReturned::class);
        $httpDispatcher->dispatch(new ServerRequest());
    }

    public function testSingleMiddlewareWorks(): void
    {
        $dispatcher = new HttpDispatcher(
            $this->middleware(static fn () => new TextResponse('foobar'))
        );

        self::assertEquals(
            'foobar',
            $dispatcher->dispatch(new ServerRequest())->getBody()->getContents()
        );
    }

    public function testAllMiddlewareAreExecutedAndReturnValuesAreRespected(): void
    {
        $executionOrder = [];

        $middleware1 = $this->middleware(
            static function (ServerRequestInterface $request, RequestHandlerInterface $next) use (&$executionOrder) {
                $executionOrder[] = 1;

                return $next->handle($request);
            }
        );

        $middleware2 = $this->middleware(
            static function ($request, $next) use (&$executionOrder) {
                $executionOrder[] = 2;

                return $next->handle($request);
            }
        );

        $middleware3 = $this->middleware(
            static function () use (&$executionOrder) {
                $executionOrder[] = 3;

                return new TextResponse(implode($executionOrder));
            }
        );

        $dispatcher = new HttpDispatcher($middleware1, $middleware2, $middleware3);

        self::assertEquals('123', $dispatcher->dispatch(new ServerRequest())->getBody()->getContents());
    }

    public function testChangesToTheRequestArePassedToLaterMiddleware(): void
    {
        $middleware1 = $this->middleware(
            static function (ServerRequestInterface $request, $next) {
                $request = $request->withAttribute('msg', 'Hello There');

                return $next->handle($request);
            }
        );

        $middleware2 = $this->middleware(
            static function (ServerRequestInterface $request, $next) {
                return new TextResponse($request->getAttribute('msg'));
            }
        );

        $dispatcher = new HttpDispatcher($middleware1, $middleware2);

        self::assertEquals(
            'Hello There',
            $dispatcher->dispatch(new ServerRequest())->getBody()->getContents()
        );
    }

    public function testOverriddenResponseIsReturned(): void
    {
        $middleware1 = $this->middleware(
            static function (ServerRequestInterface $request, $next) {
                $next->handle($request);

                return new TextResponse('Hah, overidden!');
            }
        );

        $middleware2 = $this->middleware(
            static function (ServerRequestInterface $request) {
                return new TextResponse('My response');
            }
        );

        $dispatcher = new HttpDispatcher($middleware1, $middleware2);

        self::assertEquals(
            'Hah, overidden!',
            $dispatcher->dispatch(new ServerRequest())->getBody()->getContents()
        );
    }

    private function middleware(callable $callable): MiddlewareInterface
    {
        return new class ($callable) implements MiddlewareInterface {
            /** @var callable */
            private $callable;

            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return ($this->callable)($request, $handler);
            }
        };
    }
}
