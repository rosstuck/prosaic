# Prosaic
## A boring PSR-15 Middleware dispatcher

Prosaic can run your incoming PSR-7 requests through a pipeline of PSR-15 middleware. There's no fancy
builder methods, routing or DI container integration, it's really just a pipeline of middleware. That's it.

## Installation

~~~shell script
composer require rosstuck/prosaic
~~~

## Example

You can set up your middleware pipeline like so:

~~~php
<?php

$dispatcher = new Prosaic\HttpDispatcher(
    new FirstMiddleware(),
    new SecondMiddleware(),
    new ThirdMiddleware()
);

$response = $dispatcher->dispatch($request);
~~~

## Testing
~~~shell script
composer test
~~~
