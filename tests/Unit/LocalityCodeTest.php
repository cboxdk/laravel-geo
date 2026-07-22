<?php

declare(strict_types=1);

use Cbox\Geo\Enums\TaxRegime;
use Cbox\Geo\Exceptions\InvalidLocalityCode;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\Jurisdiction;
use Cbox\Geo\ValueObjects\LocalityCode;
use Cbox\Geo\ValueObjects\SubdivisionCode;
use Cbox\Geo\ValueObjects\TaxProfile;

function usCaliforniaProfile(bool $rooftop = true): TaxProfile
{
    return new TaxProfile(TaxRegime::SalesTax, 'us-sales-tax', false, true, $rooftop);
}

function usCalifornia(?LocalityCode $locality = null): Jurisdiction
{
    return new Jurisdiction(
        country: new CountryCode('US'),
        countryName: 'United States',
        currency: 'USD',
        taxProfile: usCaliforniaProfile(),
        subdivision: new SubdivisionCode('US-CA'),
        subdivisionName: 'California',
        locality: $locality,
    );
}

it('carries an opaque per-state locality scheme and value', function () {
    $locality = new LocalityCode(new SubdivisionCode('US-CA'), 'ca-place', '06:LOS ANGELES', 'Los Angeles');

    expect($locality->scheme)->toBe('ca-place')
        ->and($locality->value)->toBe('06:LOS ANGELES')
        ->and($locality->name)->toBe('Los Angeles')
        ->and((string) $locality)->toBe('US-CA|ca-place|06:LOS ANGELES');
});

it('rejects a locality code with an empty scheme or value', function () {
    new LocalityCode(new SubdivisionCode('US-CA'), '', '123');
})->throws(InvalidLocalityCode::class);

it('compares localities by subdivision, scheme and value', function () {
    $sub = new SubdivisionCode('US-CA');
    $a = new LocalityCode($sub, 'county-fips', '06037');
    $b = new LocalityCode($sub, 'county-fips', '06037', 'Los Angeles County');
    $c = new LocalityCode($sub, 'county-fips', '06075');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});

it('flags a rooftop regime as needing rooftop until a locality is attached', function () {
    $stateOnly = usCalifornia();

    expect($stateOnly->needsRooftop())->toBeTrue()
        ->and($stateOnly->isResolvedForTax())->toBeTrue(); // subdivision is enough to assess

    $rooftop = $stateOnly->withLocality(new LocalityCode(new SubdivisionCode('US-CA'), 'county-fips', '06037'));

    expect($rooftop->needsRooftop())->toBeFalse()
        ->and($rooftop->locality?->value)->toBe('06037')
        ->and($rooftop->subdivision?->value)->toBe('US-CA'); // wither preserves the rest
});
