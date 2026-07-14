<?php

declare(strict_types=1);

namespace Cbox\Geo\ValueObjects;

use Cbox\Geo\Exceptions\InvalidSubdivisionCode;
use Stringable;

/**
 * An ISO 3166-2 subdivision code (e.g. "US-CA", "CA-QC", "CH-ZH"), carrying its
 * parent country. Sub-federal tax regimes (US, Canada) resolve against this, so
 * it is always stored bound to a {@see CountryCode} rather than as a bare string.
 */
readonly class SubdivisionCode implements Stringable
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
}
