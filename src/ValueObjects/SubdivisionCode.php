<?php

declare(strict_types=1);

namespace Cbox\Geo\ValueObjects;

use Cbox\Geo\Exceptions\InvalidSubdivisionCode;
use JsonSerializable;
use Stringable;

/**
 * An ISO 3166-2 subdivision code (e.g. "US-CA", "CA-QC", "CH-ZH"), carrying its
 * parent country. Sub-federal tax regimes (US, Canada) resolve against this, so
 * it is always stored bound to a {@see CountryCode} rather than as a bare string.
 */
readonly class SubdivisionCode implements JsonSerializable, Stringable
{
    public CountryCode $country;

    /** The subdivision part only, upper-cased (e.g. "CA" in "US-CA"). */
    public string $code;

    /** The full ISO 3166-2 code (e.g. "US-CA"). */
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (preg_match('/^([A-Z]{2})-([A-Z0-9]{1,3})$/', $normalized, $matches) !== 1) {
            throw InvalidSubdivisionCode::for($value);
        }

        $this->country = new CountryCode($matches[1]);
        $this->code = $matches[2];
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
     * `"US-CA"` on the wire — the ISO code itself, which is what an integration
     * expects and what it would have to reassemble otherwise.
     *
     * Nothing is lost: `country` and `code` are both derived from this string on
     * the way back in, so the full form round-trips through the constructor.
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
