<?php

declare(strict_types=1);

namespace Cbox\Geo\Support;

use Cbox\Geo\Enums\TaxRegime;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\TaxProfile;

/**
 * The canonical map of country → {@see TaxProfile}. This is the modelled-tax
 * layer this package owns on top of raw ISO geography; unmodelled countries
 * resolve to {@see TaxProfile::notModeled()} (deny-by-default).
 *
 * Rates and thresholds live in the tax engine's data feeds, not here — this map
 * carries only the stable structural facts (which regime, EU membership, whether
 * the jurisdiction is sub-federal, whether it needs rooftop geocoding).
 */
class TaxProfiles
{
    /**
     * ISO 3166-1 alpha-2 codes of the 27 EU member states (Greece = "GR").
     *
     * @var list<string>
     */
    private const EU_MEMBERS = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
        'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
        'SI', 'ES', 'SE',
    ];

    /** @var array<string, TaxProfile>|null */
    private static ?array $map = null;

    public static function for(CountryCode $country): TaxProfile
    {
        return self::map()[$country->value] ?? TaxProfile::notModeled();
    }

    /**
     * @return array<string, TaxProfile>
     */
    private static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $map = [];

        foreach (self::EU_MEMBERS as $code) {
            $map[$code] = new TaxProfile(TaxRegime::Vat, 'eu-vat', isEuMember: true, isSubFederal: false, requiresRooftop: false);
        }

        // Non-EU national VAT regimes.
        $map['GB'] = new TaxProfile(TaxRegime::Vat, 'uk-vat', false, false, false);
        $map['CH'] = new TaxProfile(TaxRegime::Vat, 'ch-vat', false, false, false);
        $map['NO'] = new TaxProfile(TaxRegime::Vat, 'no-vat', false, false, false);
        $map['MX'] = new TaxProfile(TaxRegime::Vat, 'mx-iva', false, false, false);

        // National GST regimes.
        $map['AU'] = new TaxProfile(TaxRegime::Gst, 'au-gst', false, false, false);
        $map['NZ'] = new TaxProfile(TaxRegime::Gst, 'nz-gst', false, false, false);
        $map['SG'] = new TaxProfile(TaxRegime::Gst, 'sg-gst', false, false, false);

        // India — dual GST (CGST+SGST intra-state vs IGST inter-state/imports). The
        // customer-facing rate is uniform across the split, so no subdivision is
        // required; the tax engine's India regime labels the components.
        $map['IN'] = new TaxProfile(TaxRegime::Gst, 'in-gst', false, isSubFederal: false, requiresRooftop: false);

        // Sub-federal regimes. Canada stacks at province level (subdivision is
        // enough); the US stacks below the state and needs rooftop geocoding.
        $map['CA'] = new TaxProfile(TaxRegime::Gst, 'ca-gst', false, isSubFederal: true, requiresRooftop: false);
        $map['US'] = new TaxProfile(TaxRegime::SalesTax, 'us-sales-tax', false, isSubFederal: true, requiresRooftop: true);

        return self::$map = $map;
    }
}
