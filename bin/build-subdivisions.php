<?php

declare(strict_types=1);

/**
 * Builds resources/iso-3166-2.php from a tagged Unicode CLDR release:
 *
 *   php bin/build-subdivisions.php [release-49]
 *
 * - `subdivisions`: every current ISO 3166-2 code with its English name.
 * - `own_country`: the ISO 3166-2 codes of territories that ISO 3166-1 also codes
 *   as a country of their own (FR-GP and FR-971 are Guadeloupe, GP), with that
 *   country code. These are NOT in `subdivisions`, so find() denies them.
 *
 * Why CLDR: commerceguys/addressing only knows the subdivisions a postal address
 * uses (no German Länder, no Spanish autonomous communities), while CLDR tracks all
 * of ISO 3166-2 and marks abolished codes as deprecated, so a code that no longer
 * exists (the Danish counties of 2007) does not come back. Unicode-3.0 licence.
 *
 * Why own-country territories are denied rather than resolved: as a subdivision of
 * France, Guadeloupe would be read as France, and a tax engine would charge French
 * VAT on a sale outside the EU VAT area. Every established tax API (Stripe, Paddle)
 * takes them by their own country code instead.
 *
 * Requires `composer install`: the own-country codes are checked against the
 * addressing country list, so a hint never names a code that is not a country.
 */

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Exception\UnknownCountryException;

require __DIR__.'/../vendor/autoload.php';

$release = $argv[1] ?? 'release-49';
$base = "https://raw.githubusercontent.com/unicode-org/cldr/{$release}/common";

/**
 * Regular CLDR codes for territories that also have an ISO 3166-1 code. CLDR only
 * aliases the letter forms ("frgp" => GP) and keeps the INSEE numbers and the
 * Caribbean Netherlands as ordinary subdivisions, so they are named here.
 */
$alsoCountries = [
    'fr971' => 'GP', 'fr972' => 'MQ', 'fr973' => 'GF', 'fr974' => 'RE', 'fr976' => 'YT',
    'nlbq1' => 'BQ', 'nlbq2' => 'BQ', 'nlbq3' => 'BQ',
];

$fetch = static function (string $path) use ($base): string {
    $body = file_get_contents("{$base}/{$path}");

    if ($body === false || $body === '') {
        fwrite(STDERR, "Could not fetch {$base}/{$path}\n");
        exit(1);
    }

    return $body;
};

/** CLDR's compact range syntax: "ad02~8" is ad02 to ad08, "ara~h" is ara to arh. */
$expand = static function (string $list): array {
    $out = [];

    foreach (preg_split('/\s+/', trim($list)) ?: [] as $token) {
        if (! str_contains($token, '~')) {
            $out[] = $token;

            continue;
        }

        // The range only ever varies the last character in CLDR's files.
        [$start, $end] = explode('~', $token, 2);

        if (strlen($end) !== 1) {
            fwrite(STDERR, "Unexpected range {$token}\n");
            exit(1);
        }

        for ($c = ord(substr($start, -1)); $c <= ord($end); $c++) {
            $out[] = substr($start, 0, -1).chr($c);
        }
    }

    return $out;
};

$iso = static fn (string $id): string => strtoupper(substr($id, 0, 2)).'-'.strtoupper(substr($id, 2));

$validity = new SimpleXMLElement($fetch('validity/subdivision.xml'));
$metadata = new SimpleXMLElement($fetch('supplemental/supplementalMetadata.xml'));
$names = new SimpleXMLElement($fetch('subdivisions/en.xml'));

// CLDR "overlong": an ISO 3166-2 code CLDR replaces with the territory's own
// country code (FI-01 => AX, US-PR => PR, FR-GP => GP).
foreach ($metadata->xpath('//subdivisionAlias[@reason="overlong"]') ?: [] as $alias) {
    $alsoCountries[(string) $alias['type']] = (string) $alias['replacement'];
}

$countries = new CountryRepository;
$ownCountry = [];
foreach ($alsoCountries as $id => $country) {
    try {
        $countries->get($country);
        $ownCountry[$iso($id)] = $country;
    } catch (UnknownCountryException) {
        // Tristan da Cunha (TA) and Clipperton (CP) are reserved, not countries:
        // denied with no code to suggest.
        $ownCountry[$iso($id)] = null;
    }
}

$regular = [];
foreach ($validity->xpath('//id[@type="subdivision"][@idStatus="regular"]') ?: [] as $group) {
    foreach ($expand((string) $group) as $id) {
        $regular[] = $id;
    }
}

$english = [];
foreach ($names->xpath('//subdivision') ?: [] as $name) {
    // CLDR marks some names with footnote digits ("Île-de-France²").
    $english[(string) $name['type']] = trim(preg_replace('/[\x{00B9}\x{00B2}\x{00B3}\x{2070}-\x{2079}]+$/u', '', (string) $name) ?? '');
}

$subdivisions = [];
foreach ($regular as $id) {
    $code = $iso($id);

    if (array_key_exists($code, $ownCountry)) {
        continue;
    }

    if (preg_match('/^[A-Z]{2}-[A-Z0-9]{1,3}$/', $code) !== 1 || ($english[$id] ?? '') === '') {
        fwrite(STDERR, "Unexpected CLDR entry {$id}\n");
        exit(1);
    }

    $subdivisions[$code] = $english[$id];
}

ksort($subdivisions, SORT_STRING);
ksort($ownCountry, SORT_STRING);

$export = static function (array $map): string {
    $lines = [];
    foreach ($map as $key => $value) {
        $lines[] = '        '.var_export($key, true).' => '.var_export($value, true).',';
    }

    return implode("\n", $lines);
};

$php = <<<'PHP'
<?php

// GENERATED by bin/build-subdivisions.php from Unicode CLDR %s. Do not edit.
// Data: Copyright © Unicode, Inc. SPDX-License-Identifier: Unicode-3.0
// https://www.unicode.org/license.txt

return [
    // Every current ISO 3166-2 code with its English name.
    'subdivisions' => [
%s
    ],
    // Territories ISO 3166-1 also codes as a country: the country code to send instead.
    'own_country' => [
%s
    ],
];

PHP;

$target = __DIR__.'/../resources/iso-3166-2.php';
@mkdir(dirname($target));
file_put_contents($target, sprintf($php, $release, $export($subdivisions), $export($ownCountry)));

fwrite(STDOUT, count($subdivisions).' subdivisions and '.count($ownCountry)." own-country territories written\n");
