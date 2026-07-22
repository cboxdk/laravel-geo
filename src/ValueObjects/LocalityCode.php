<?php

declare(strict_types=1);

namespace Cbox\Geo\ValueObjects;

use Cbox\Geo\Exceptions\InvalidLocalityCode;
use Stringable;

/**
 * A sub-subdivision (rooftop) taxing-locality identifier — a county, city, or
 * special district BELOW the ISO 3166-2 subdivision. Unlike {@see SubdivisionCode}
 * there is no single canonical format: US sales-tax jurisdictions are keyed
 * differently per state (county FIPS for the Streamlined states, comptroller
 * authority codes for Texas/Alabama, "06:PLACENAME" for California, location ids
 * for Illinois). This value object therefore does not impose one regex; it carries
 * a `scheme` naming the coding system and an opaque `value` in that system, bound
 * to the parent {@see SubdivisionCode}.
 *
 * It is a typed CARRIER, not a resolver: geo ships no rooftop reference data, so
 * this is populated by a rooftop-capable geocoder/repository the host binds, and
 * consumed by a rate source that keys its local-rate dataset off `(scheme, value)`.
 * Deny-by-default is preserved — absent a locality the caller falls back to the
 * subdivision rate rather than guessing a locality.
 */
readonly class LocalityCode implements Stringable
{
    public function __construct(
        public SubdivisionCode $subdivision,
        /** The coding system the value is expressed in, e.g. "county-fips", "tx-authority", "ca-place". */
        public string $scheme,
        /** The locality key in that scheme, verbatim as the rate dataset carries it. */
        public string $value,
        /** Human-readable name where the resolver has one (county/city name). */
        public ?string $name = null,
    ) {
        $scheme = trim($scheme);
        $value = trim($value);

        if ($scheme === '' || $value === '') {
            throw InvalidLocalityCode::for($scheme, $value);
        }
    }

    public function equals(self $other): bool
    {
        return $this->subdivision->equals($other->subdivision)
            && $this->scheme === $other->scheme
            && $this->value === $other->value;
    }

    /** A stable, greppable key: "US-CA|ca-place|06:LOS ANGELES". */
    public function __toString(): string
    {
        return $this->subdivision->value.'|'.$this->scheme.'|'.$this->value;
    }
}
