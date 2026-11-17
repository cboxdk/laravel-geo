<?php

declare(strict_types=1);

namespace Cbox\Geo\Repositories;

use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\Exceptions\InvalidSubdivisionCode;
use Cbox\Geo\Support\TaxProfiles;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\Jurisdiction;
use Cbox\Geo\ValueObjects\Subdivision;
use Cbox\Geo\ValueObjects\SubdivisionCode;
use CommerceGuys\Addressing\Country\Country;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Exception\UnknownCountryException;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

/**
 * Resolves jurisdictions from the ISO reference data shipped in commerceguys/
 * addressing, layering this package's {@see TaxProfiles} on top. find() also
 * accepts the ISO 3166-2 codes an address form does not use (from Unicode CLDR, in
 * resources/iso-3166-2.php); subdivisions() lists only the address ones. All lookups are
 * deny-by-default: an unknown country or a subdivision that does not belong to
 * its country returns `null`, never a guess.
 */
readonly class AddressingJurisdictionRepository implements JurisdictionRepository
{
    public function __construct(
        private CountryRepository $countries,
        private SubdivisionRepository $subdivisions,
        private string $locale = 'en',
    ) {}

    public function countries(): array
    {
        $out = [];

        foreach ($this->countries->getAll($this->locale) as $country) {
            $out[] = $this->jurisdictionFor($country);
        }

        return $out;
    }

    public function find(CountryCode $country, ?SubdivisionCode $subdivision = null): ?Jurisdiction
    {
        try {
            $resolved = $this->countries->get($country->value, $this->locale);
        } catch (UnknownCountryException) {
            return null;
        }

        if ($subdivision === null) {
            return $this->jurisdictionFor($resolved);
        }

        // A subdivision must belong to the country it is being resolved against.
        if (! $subdivision->country->equals($country)) {
            return null;
        }

        $sub = $this->subdivisions->get($subdivision->code, [$country->value]);

        if ($sub !== null) {
            return $this->jurisdictionFor($resolved, $subdivision, $sub->getName());
        }

        // The addressing data holds only the subdivisions a postal address uses: no
        // German Länder, no Spanish autonomous communities (ES-MD, ES-CN), no Åland
        // (FI-01). Those are still real ISO 3166-2 codes, so they resolve from the
        // full list; a code on neither list stays denied.
        $iso = self::iso31662();

        if (! array_key_exists($subdivision->value, $iso)) {
            return null;
        }

        return $this->jurisdictionFor($resolved, $subdivision, $iso[$subdivision->value]);
    }

    public function subdivisions(CountryCode $country): array
    {
        $out = [];

        foreach ($this->subdivisions->getAll([$country->value]) as $sub) {
            // getId(), NOT getCode(). The addressing library's `code` is the
            // POSTAL/display abbreviation — "A Coruña" for Spain, "Hokkaido" for
            // Japan, "Ags." for Aguascalientes — while `id` is the ISO 3166-2 code
            // this package is built to speak (ES-C, JP-01, MX-AGU).
            //
            // Reading `code` failed in two directions at once, and the second is
            // the dangerous one. Most values were rejected by the format check and
            // silently skipped, so Spain returned 0 of its 52 subdivisions. But a
            // display name that happens to be short and alphanumeric slipped
            // THROUGH as a plausible-looking invention: Japan's "Mie" became
            // `JP-MIE`, which is not an ISO code at all — the real one is `JP-24`.
            $iso = $sub->getId();

            if ($iso === '') {
                continue;
            }

            try {
                $code = new SubdivisionCode($country->value.'-'.$iso);
            } catch (InvalidSubdivisionCode) {
                // Not an ISO 3166-2 code we bind selects to; skip it.
                continue;
            }

            $out[] = new Subdivision($code, $sub->getName());
        }

        return $out;
    }

    /**
     * Every current ISO 3166-2 code with its English name, generated from Unicode
     * CLDR by bin/build-subdivisions.php.
     *
     * @return array<string, ?string>
     */
    private static function iso31662(): array
    {
        /** @var array<string, ?string>|null $codes */
        static $codes = null;

        /** @var array<string, ?string> $loaded */
        $loaded = $codes ??= require __DIR__.'/../../resources/iso-3166-2.php';

        return $loaded;
    }

    private function jurisdictionFor(
        Country $country,
        ?SubdivisionCode $subdivision = null,
        ?string $subdivisionName = null,
    ): Jurisdiction {
        $code = new CountryCode($country->getCountryCode());

        return new Jurisdiction(
            country: $code,
            countryName: $country->getName(),
            currency: $country->getCurrencyCode() ?? '',
            taxProfile: TaxProfiles::for($code),
            subdivision: $subdivision,
            subdivisionName: $subdivisionName,
        );
    }
}
