<?php

declare(strict_types=1);

namespace Cbox\Geo\ValueObjects;

use Cbox\Geo\Exceptions\InvalidCountryCode;
use JsonSerializable;
use Stringable;

/**
 * An ISO 3166-1 alpha-2 country code, normalised to upper case.
 *
 * This enforces the *shape* of a code (two letters) so nothing downstream ever
 * matches on free text. Whether the code names a country we actually support is
 * decided at the repository boundary (deny-by-default): a well-formed but unknown
 * code resolves to `null`, never a guess.
 */
readonly class CountryCode implements JsonSerializable, Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (preg_match('/^[A-Z]{2}$/', $normalized) !== 1) {
            throw InvalidCountryCode::for($value);
        }

        $this->value = $normalized;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * `"DK"` on the wire, not `{"value":"DK"}`.
     *
     * Encoding an object of public readonly properties Just Works, which is the
     * problem: without this, the wrapper that exists to keep free text out of the
     * domain leaks into every payload anything containing a country code is encoded
     * into. That shape then becomes an API's response format by accident rather
     * than by decision, and undoing it later is somebody else's broken integration.
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
