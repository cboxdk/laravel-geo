<?php

declare(strict_types=1);

namespace Cbox\Geo\Exceptions;

use InvalidArgumentException;

class InvalidSubdivisionCode extends InvalidArgumentException
{
    public static function for(string $value): self
    {
        return new self(sprintf(
            'Not a valid ISO 3166-2 subdivision code: "%s". Expected COUNTRY-SUBDIVISION (e.g. "US-CA").',
            $value,
        ));
    }
}
