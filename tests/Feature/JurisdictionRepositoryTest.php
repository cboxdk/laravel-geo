<?php

declare(strict_types=1);

use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\Support\Iso31662;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\SubdivisionCode;

beforeEach(function () {
    $this->repo = $this->app->make(JurisdictionRepository::class);
});

it('resolves a national jurisdiction with its currency and tax profile', function () {
    $dk = $this->repo->find(new CountryCode('DK'));

    expect($dk)->not->toBeNull()
        ->and($dk->countryName)->toBe('Denmark')
        ->and($dk->currency)->toBe('DKK')
        ->and($dk->taxProfile->regimeModule)->toBe('eu-vat')
        ->and($dk->isResolvedForTax())->toBeTrue()
        ->and($dk->needsSubdivision())->toBeFalse();
});

it('flags a sub-federal country as needing a subdivision until one is given', function () {
    $us = $this->repo->find(new CountryCode('US'));

    expect($us->needsSubdivision())->toBeTrue()
        ->and($us->isResolvedForTax())->toBeFalse();

    $california = $this->repo->find(new CountryCode('US'), new SubdivisionCode('US-CA'));

    expect($california)->not->toBeNull()
        ->and($california->subdivisionName)->toBe('California')
        ->and($california->isResolvedForTax())->toBeTrue();
});

it('denies an unknown country', function () {
    expect($this->repo->find(new CountryCode('ZZ')))->toBeNull();
});

it('denies an unknown subdivision', function () {
    expect($this->repo->find(new CountryCode('US'), new SubdivisionCode('US-ZZ')))->toBeNull();
});

// The addressing data holds only the subdivisions a postal address uses, so these
// real ISO 3166-2 codes were denied: Germany and France list none, Spain lists its
// provinces but not its autonomous communities, Portugal lists none.
it('resolves an ISO 3166-2 code that no address form uses', function (string $code, string $name) {
    $j = $this->repo->find(new CountryCode(substr($code, 0, 2)), new SubdivisionCode($code));

    expect($j)->not->toBeNull()
        ->and($j->subdivision->value)->toBe($code)
        ->and($j->subdivisionName)->toBe($name);
})->with([
    ['DE-BE', 'Berlin'],
    ['FR-IDF', 'Île-de-France'],
    ['ES-MD', 'Madrid Autonomous Community'],
    ['ES-CN', 'Canary Islands'],
    ['PT-20', 'Azores'],
]);

it('still denies an abolished or invented ISO-shaped code', function (string $code) {
    expect($this->repo->find(new CountryCode(substr($code, 0, 2)), new SubdivisionCode($code)))->toBeNull();
})->with([
    'DK-015', // Københavns Amt, abolished in 2007
    'FR-75',  // Paris before it became FR-75C
    'ES-ZZ',
    'DE-XX',
]);

// Guadeloupe is both FR-GP (and FR-971) and the country GP. Resolved as France it
// would carry France's tax profile, and a sale outside the EU VAT area would be
// charged French VAT. Denied, with the country code to send instead.
it('denies a territory that has its own country code, and names that code', function (string $code, string $country) {
    $subdivision = new SubdivisionCode($code);

    expect($this->repo->find($subdivision->country, $subdivision))->toBeNull()
        ->and(Iso31662::ownCountry($subdivision)?->value)->toBe($country);
})->with([
    ['FR-GP', 'GP'],
    ['FR-971', 'GP'],
    ['FR-974', 'RE'],
    ['FR-NC', 'NC'],
    ['NL-AW', 'AW'],
    ['NL-BQ1', 'BQ'],
    ['FI-01', 'AX'],
]);

it('names no own country for an ordinary subdivision', function () {
    expect(Iso31662::ownCountry(new SubdivisionCode('ES-CN')))->toBeNull()
        ->and(Iso31662::ownCountry(new SubdivisionCode('DE-BE')))->toBeNull();
});

it('keeps resolving the US territories the address data lists under the US', function () {
    expect($this->repo->find(new CountryCode('US'), new SubdivisionCode('US-PR')))->not->toBeNull();
});

it('denies a subdivision that belongs to a different country', function () {
    expect($this->repo->find(new CountryCode('US'), new SubdivisionCode('CA-QC')))->toBeNull();
});

it('lists subdivisions bound to ISO codes for selects', function () {
    $provinces = $this->repo->subdivisions(new CountryCode('CA'));

    $codes = array_map(fn ($s) => $s->code->value, $provinces);

    expect($codes)->toContain('CA-QC', 'CA-ON');
});

// Canada is the worst country to test this with on its own: its postal
// abbreviations and its ISO codes are identical, so reading the wrong field
// passes anyway. These are the countries where the two diverge.
it('emits real ISO codes, not the postal display abbreviations', function () {
    // Spain's display codes are full place names ("A Coruña"), which fail the
    // format check — so reading them returned an EMPTY list for the whole country.
    $spain = $this->repo->subdivisions(new CountryCode('ES'));
    $spanish = array_map(fn ($s) => $s->code->value, $spain);

    expect($spain)->toHaveCount(52)
        ->and($spanish)->toContain('ES-C')    // A Coruña
        ->and($spanish)->toContain('ES-VI')   // Álava
        ->and($spanish)->not->toContain('ES-A Coruña');
});

it('does not invent a code that merely looks ISO-shaped', function () {
    // The dangerous half. Most display names were rejected and skipped, but a
    // short alphanumeric one slipped THROUGH as a plausible invention: Japan's
    // "Mie" became JP-MIE and India's "Goa" became IN-GOA. Neither is an ISO code
    // — Mie is JP-24, Goa is IN-GA — and nothing anywhere said so.
    $japan = array_map(fn ($s) => $s->code->value, $this->repo->subdivisions(new CountryCode('JP')));
    $india = array_map(fn ($s) => $s->code->value, $this->repo->subdivisions(new CountryCode('IN')));

    expect($japan)->toContain('JP-24')->and($japan)->not->toContain('JP-MIE')
        ->and($india)->toContain('IN-GA')->and($india)->not->toContain('IN-GOA');
});

it('resolves a jurisdiction for every code it lists', function () {
    // The property that matters: a code offered in a select must be one find()
    // accepts. Listing codes nothing can resolve is how a fabricated code reaches
    // a tax assessment.
    foreach (['ES', 'MX', 'JP', 'IN', 'CA'] as $country) {
        $code = new CountryCode($country);

        foreach ($this->repo->subdivisions($code) as $subdivision) {
            expect($this->repo->find($code, $subdivision->code))
                ->not->toBeNull("{$subdivision->code->value} was listed but does not resolve");
        }
    }
});

it('enumerates countries for a select', function () {
    $countries = $this->repo->countries();

    $codes = array_map(fn ($j) => $j->country->value, $countries);

    expect($codes)->toContain('DK', 'US', 'GB', 'AU');
});
