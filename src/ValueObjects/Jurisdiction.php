<?php

declare(strict_types=1);

namespace Cbox\Geo\ValueObjects;

/**
 * A resolved jurisdiction: a country (optionally narrowed to a subdivision, and —
 * for rooftop-sourced regimes — to a {@see LocalityCode} below it) with its ISO
 * geography (name, ISO 4217 currency) and its {@see TaxProfile}. This is the join
 * key that seller registrations, buyer addresses, tax-rate sources and regime
 * modules all bind to.
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
        /**
         * A rooftop taxing locality below the subdivision (US county/city/special
         * district). Null unless a rooftop-capable geocoder resolved one; geo ships
         * no rooftop reference data of its own.
         */
        public ?LocalityCode $locality = null,
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
     * True when the jurisdiction's regime needs rooftop (address-level) resolution
     * but no locality has been supplied — the signal for a caller to run a rooftop
     * geocoder, or to fall back to the subdivision rate at reduced confidence.
     */
    public function needsRooftop(): bool
    {
        return $this->taxProfile->requiresRooftop && $this->locality === null;
    }

    /**
     * Whether this jurisdiction is specified finely enough for a tax engine to
     * act on it: national regimes always are; sub-federal ones need a subdivision.
     * Rooftop resolution is a confidence refinement, not a gate — a subdivision is
     * enough to assess (at the subdivision rate), so it is not required here.
     */
    public function isResolvedForTax(): bool
    {
        return ! $this->taxProfile->isSubFederal || $this->subdivision !== null;
    }

    /**
     * Return a copy narrowed to the given rooftop locality. Additive and
     * non-mutating (the VO is readonly): a rooftop geocoder builds the base
     * jurisdiction, then attaches the locality it resolved.
     */
    public function withLocality(LocalityCode $locality): self
    {
        return new self(
            $this->country,
            $this->countryName,
            $this->currency,
            $this->taxProfile,
            $this->subdivision,
            $this->subdivisionName,
            $locality,
        );
    }
}
