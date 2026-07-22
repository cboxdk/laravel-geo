<?php

declare(strict_types=1);

namespace Cbox\Geo\Exceptions;

use InvalidArgumentException;

class InvalidLocalityCode extends InvalidArgumentException
{
    public static function for(string $scheme, string $value): self
    {
        return new self(sprintf(
            'Not a valid locality code: scheme "%s", value "%s". Both a non-empty scheme and value are required.',
            $scheme,
            $value,
        ));
    }
}
