<?php

declare(strict_types=1);

namespace Prosaic\RequestHandler;

use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

final class CallableRequestHandlerTest extends TestCase
{
    public function testCallableIsExecutedAndReturnsResponse(): void
    {
        $response = new TextResponse('derp');

        $requestHandler = new CallableRequestHandler(static fn () => $response);

        self::assertEquals(
            $response,
            $requestHandler->handle(new ServerRequest())
        );
    }

    public function testCallableReceivesRequest(): void
    {
        $request = (new ServerRequest())
            ->withAttribute('msg', 'Hello There');

        $requestHandler = new CallableRequestHandler(
            static fn (ServerRequest $request) => new TextResponse($request->getAttribute('msg'))
        );

        self::assertEquals(
            'Hello There',
            $requestHandler->handle($request)->getBody()->getContents()
        );
    }
}
