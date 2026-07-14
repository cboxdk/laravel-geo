<?php

declare(strict_types=1);

namespace Cbox\Geo\ValueObjects;

/**
 * A subdivision option: its ISO 3166-2 code plus a display name. Returned as a
 * list to back country → subdivision selects, so a UI never free-types a state.
 */
readonly class Subdivision
{
    public function __construct(
        public SubdivisionCode $code,
        public string $name,
    ) {}
}
