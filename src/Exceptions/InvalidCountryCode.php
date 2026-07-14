<?php

declare(strict_types=1);

namespace Cbox\Geo\Exceptions;

use InvalidArgumentException;

class InvalidCountryCode extends InvalidArgumentException
{
    public static function for(string $value): self
    {
        return new self(sprintf(
            'Not a valid ISO 3166-1 alpha-2 country code: "%s". Expected two letters (e.g. "DK").',
            $value,
        ));
    }
}
