<?php

declare(strict_types=1);

namespace Cbox\Geo\Support;

use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\SubdivisionCode;
use RuntimeException;

/**
 * The full ISO 3166-2 list, generated from Unicode CLDR into
 * resources/iso-3166-2.php by bin/build-subdivisions.php.
 *
 * Wider than an address form's list: it has the German Länder, the Spanish
 * autonomous communities and the French regions, which postal addresses do not
 * use. It leaves out the territories ISO 3166-1 also codes as a country (FR-GP is
 * Guadeloupe, GP); {@see self::ownCountry()} names the code to use for those.
 */
final class Iso31662
{
    /** @var array{subdivisions: array<string>, own_country: array<string>}|null */
    private static ?array $data = null;

    /** The English name of a current ISO 3166-2 code; null when it is not one. */
    public static function name(SubdivisionCode $subdivision): ?string
    {
        return self::data()['subdivisions'][$subdivision->value] ?? null;
    }

    /**
     * The country code of a territory ISO 3166-1 codes as a country of its own, such
     * as GP for FR-GP and FR-971; null for every other code.
     */
    public static function ownCountry(SubdivisionCode $subdivision): ?CountryCode
    {
        $country = self::data()['own_country'][$subdivision->value] ?? null;

        return $country === null ? null : new CountryCode($country);
    }

    /** @return array{subdivisions: array<string>, own_country: array<string>} */
    private static function data(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $data = require __DIR__.'/../../resources/iso-3166-2.php';

        if (! is_array($data) || ! is_array($data['subdivisions'] ?? null) || ! is_array($data['own_country'] ?? null)) {
            throw new RuntimeException('resources/iso-3166-2.php is not the generated list; run bin/build-subdivisions.php.');
        }

        return self::$data = [
            'subdivisions' => array_filter($data['subdivisions'], is_string(...)),
            'own_country' => array_filter($data['own_country'], is_string(...)),
        ];
    }
}
