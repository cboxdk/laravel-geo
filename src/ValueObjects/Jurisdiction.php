<?php

declare(strict_types=1);

namespace Cbox\Geo\ValueObjects;

/**
 * A resolved jurisdiction: a country (optionally narrowed to a subdivision) with
 * its ISO geography (name, ISO 4217 currency) and its {@see TaxProfile}. This is
 * the join key that seller registrations, buyer addresses, tax-rate sources and
 * regime modules all bind to.
 */
readonly class Jurisdiction
{
    public function __construct(
        public CountryCode $country,
        public string $countryName,
        /** ISO 4217 currency code, sourced from the ISO reference data. */
        public string $currency,
        public TaxProfile $taxProfile,
        public ?SubdivisionCode $subdivision = null,
        public ?string $subdivisionName = null,
    ) {}

    /**
     * True when a subdivision is required for a correct tax outcome but none has
     * been supplied — the caller must resolve one (or deny) rather than proceed.
     */
    public function needsSubdivision(): bool
    {
        return $this->taxProfile->isSubFederal && $this->subdivision === null;
    }

    /**
     * Whether this jurisdiction is specified finely enough for a tax engine to
     * act on it: national regimes always are; sub-federal ones need a subdivision.
     */
    public function isResolvedForTax(): bool
    {
        return ! $this->taxProfile->isSubFederal || $this->subdivision !== null;
    }
}
