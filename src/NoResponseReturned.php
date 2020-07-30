<?php

declare(strict_types=1);

namespace Prosaic;

use Exception;

final class NoResponseReturned extends Exception
{
    public static function create(): self
    {
        return new self(
            'No ResponseInterface instance was returned in the list of middleware. When handling a request, a ' .
            'ResponseInterface instance must always be returned, even if it\'s only an error page. For more details, ' .
            'please see the PSR-15 documentation: https://www.php-fig.org/psr/psr-15/'
        );
    }
}
