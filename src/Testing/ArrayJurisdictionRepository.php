<?php

declare(strict_types=1);

namespace Cbox\Geo\Testing;

use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\Enums\TaxRegime;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\Jurisdiction;
use Cbox\Geo\ValueObjects\Subdivision;
use Cbox\Geo\ValueObjects\SubdivisionCode;
use Cbox\Geo\ValueObjects\TaxProfile;

/**
 * An in-memory {@see JurisdictionRepository} for tests and fixtures.
 *
 * The shipped repository reads real ISO reference data, which is right in
 * production and awkward in a test: every case has to be a country that genuinely
 * exists and genuinely behaves the way the case needs. A test for "a sub-federal
 * regime that requires rooftop resolution" should be able to say exactly that,
 * rather than reaching for the United States and inheriting everything else true
 * about it — and a test for a country with no VAT at all should not depend on
 * which countries currently have none.
 *
 * Deny-by-default is preserved: anything not registered resolves to `null`, the
 * same answer the real repository gives for a code it does not recognise.
 */
readonly class ArrayJurisdictionRepository implements JurisdictionRepository
{
    /**
     * @param  array<string, Jurisdiction>  $jurisdictions  keyed "DK" or "US-CA"
     * @param  array<string, list<Subdivision>>  $subdivisions  keyed by country code
     */
    public function __construct(
        private array $jurisdictions = [],
        private array $subdivisions = [],
    ) {}

    /**
     * A repository holding one plain national VAT country — enough for the many
     * tests whose subject is not geography at all.
     */
    public static function withVatCountry(string $country = 'DK', string $currency = 'DKK'): self
    {
        return new self([$country => self::vat($country, $currency)]);
    }

    /** A national VAT jurisdiction: one authority, no subdivision, no rooftop. */
    public static function vat(string $country, string $currency = 'EUR', bool $euMember = true): Jurisdiction
    {
        return new Jurisdiction(
            new CountryCode($country),
            'Test '.$country,
            $currency,
            new TaxProfile(TaxRegime::Vat, 'eu-vat', $euMember, false, false),
        );
    }

    /**
     * A sub-federal sales-tax jurisdiction — the shape that needs a subdivision to
     * be correct, and optionally an address to be precise.
     */
    public static function salesTax(
        string $subdivision,
        string $currency = 'USD',
        bool $requiresRooftop = true,
    ): Jurisdiction {
        $code = new SubdivisionCode($subdivision);

        return new Jurisdiction(
            $code->country,
            'Test '.$code->country->value,
            $currency,
            new TaxProfile(TaxRegime::SalesTax, 'us-sales-tax', false, true, $requiresRooftop),
            $code,
            'Test '.$code->code,
        );
    }

    /** A jurisdiction that levies nothing — the case that must not become 0% by accident. */
    public static function untaxed(string $country, string $currency = 'USD'): Jurisdiction
    {
        return new Jurisdiction(
            new CountryCode($country),
            'Test '.$country,
            $currency,
            new TaxProfile(TaxRegime::None, null, false, false, false),
        );
    }

    /** @param  array<string, Jurisdiction>  $jurisdictions */
    public function with(array $jurisdictions): self
    {
        return new self([...$this->jurisdictions, ...$jurisdictions], $this->subdivisions);
    }

    /** @param  array<string, list<Subdivision>>  $subdivisions */
    public function withSubdivisions(array $subdivisions): self
    {
        return new self($this->jurisdictions, [...$this->subdivisions, ...$subdivisions]);
    }

    public function countries(): array
    {
        return array_values(array_filter(
            $this->jurisdictions,
            static fn (Jurisdiction $j): bool => $j->subdivision === null,
        ));
    }

    public function find(CountryCode $country, ?SubdivisionCode $subdivision = null): ?Jurisdiction
    {
        $key = $subdivision !== null ? $subdivision->value : $country->value;

        $found = $this->jurisdictions[$key] ?? null;

        // A subdivision of a country this repository does not hold is not a partial
        // match to be filled in — it is unknown, the same as the real one would say.
        if ($found === null || ! $found->country->equals($country)) {
            return null;
        }

        return $found;
    }

    public function subdivisions(CountryCode $country): array
    {
        return $this->subdivisions[$country->value] ?? [];
    }
}
