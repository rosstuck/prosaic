<?php

declare(strict_types=1);

namespace Prosaic;

use Closure;
use Prosaic\RequestHandler\CallableRequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

use function array_reduce;
use function array_reverse;

final class HttpDispatcher
{
    private Closure $middlewareChain;

    public function __construct(MiddlewareInterface ...$middleware)
    {
        $this->middlewareChain = $this->createMiddlewareChain(...$middleware);
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->middlewareChain)($request);
    }

    private function createMiddlewareChain(MiddlewareInterface ...$middlewareList): Closure
    {
        return array_reduce(
            array_reverse($middlewareList),
            static function ($next, $middleware) {
                return static fn ($request) => $middleware->process($request, new CallableRequestHandler($next));
            },
            static function (): void {
                throw NoResponseReturned::create();
            }
        );
    }
}
