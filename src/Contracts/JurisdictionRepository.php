<?php

declare(strict_types=1);

namespace Cbox\Geo\Contracts;

use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\Jurisdiction;
use Cbox\Geo\ValueObjects\Subdivision;
use Cbox\Geo\ValueObjects\SubdivisionCode;

/**
 * Resolves and enumerates jurisdictions from canonical ISO reference data. Bind
 * this contract; depend on it rather than any concrete data source so the host
 * can swap the backing data (or persist it) without touching callers.
 *
 * Resolution is deny-by-default: an unknown country or subdivision returns
 * `null`, never a best-guess match.
 */
interface JurisdictionRepository
{
    /**
     * Every supported country, for building a country select.
     *
     * @return list<Jurisdiction>
     */
    public function countries(): array;

    /**
     * Resolve a jurisdiction, optionally narrowed to a subdivision. Returns
     * `null` if the country (or a supplied subdivision) is not recognised.
     */
    public function find(CountryCode $country, ?SubdivisionCode $subdivision = null): ?Jurisdiction;

    /**
     * The subdivisions of a country, for building a subdivision select. Empty
     * for countries with no relevant subdivisions.
     *
     * @return list<Subdivision>
     */
    public function subdivisions(CountryCode $country): array;
}
