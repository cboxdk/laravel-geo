<?php

declare(strict_types=1);

namespace Cbox\Geo\ValueObjects;

use Cbox\Geo\Enums\TaxRegime;

/**
 * The tax-relevant attributes of a jurisdiction — the value this package adds on
 * top of the raw ISO geography. Downstream engines read these as DATA instead of
 * branching on hard-coded country lists.
 *
 * `regimeModule` is a stable string key (e.g. "eu-vat", "us-sales-tax") that a
 * tax engine maps to a concrete regime implementation; this package intentionally
 * does not know those classes (tax depends on geo, never the reverse).
 */
readonly class TaxProfile
{
    public function __construct(
        public TaxRegime $regime,
        public ?string $regimeModule,
        /** Member of the EU VAT area — drives reverse-charge / OSS routing. */
        public bool $isEuMember,
        /** Tax is set below the national level — a subdivision is required to be correct (US, CA). */
        public bool $isSubFederal,
        /** Rate resolution needs rooftop/address-level geocoding, not just a subdivision (US). */
        public bool $requiresRooftop,
    ) {}

    /**
     * A country we have not modelled. Deny-by-default: never assume a taxable
     * regime for an unknown jurisdiction.
     */
    public static function notModeled(): self
    {
        return new self(TaxRegime::None, null, false, false, false);
    }

    public function isModeled(): bool
    {
        return $this->regime !== TaxRegime::None;
    }
}
