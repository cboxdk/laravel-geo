<?php

declare(strict_types=1);

use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\Enums\TaxRegime;
use Cbox\Geo\Testing\ArrayJurisdictionRepository;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\SubdivisionCode;

// This package shipped the contract everything else in the platform binds to, and
// no way to fake it. So a test elsewhere whose subject was tax logic had to load
// the real ISO reference data to get a Jurisdiction, and had to express every case
// through a country that genuinely behaves that way — inheriting everything else
// true about that country along with it.

it('resolves only what it was given', function () {
    $repository = $this->fakeGeo(['DK' => ArrayJurisdictionRepository::vat('DK', 'DKK')]);

    expect($repository->find(new CountryCode('DK'))?->currency)->toBe('DKK')
        // Deny-by-default, the same answer the real repository gives for a code it
        // does not recognise. A fake that invented a country would let a test pass
        // against behaviour production does not have.
        ->and($repository->find(new CountryCode('NO')))->toBeNull();
});

it('lets a test state the tax SHAPE it needs instead of naming a country', function () {
    $repository = $this->fakeGeo([
        'US-CA' => ArrayJurisdictionRepository::salesTax('US-CA'),
        'XX' => ArrayJurisdictionRepository::untaxed('XX'),
    ]);

    $ca = $repository->find(new CountryCode('US'), new SubdivisionCode('US-CA'));

    expect($ca?->taxProfile->isSubFederal)->toBeTrue()
        ->and($ca?->needsRooftop())->toBeTrue()
        ->and($repository->find(new CountryCode('XX'))?->taxProfile->regime)->toBe(TaxRegime::None);
});

it('does not hand back a subdivision belonging to another country', function () {
    // "US-CA" and "CA" share letters, and a lookup keyed on the wrong one must not
    // half-match into a plausible answer.
    $repository = $this->fakeGeo(['US-CA' => ArrayJurisdictionRepository::salesTax('US-CA')]);

    expect($repository->find(new CountryCode('CA'), new SubdivisionCode('US-CA')))->toBeNull();
});

it('lists only countries as countries, not their subdivisions', function () {
    $repository = $this->fakeGeo([
        'DK' => ArrayJurisdictionRepository::vat('DK', 'DKK'),
        'US-CA' => ArrayJurisdictionRepository::salesTax('US-CA'),
    ]);

    expect($repository->countries())->toHaveCount(1)
        ->and($repository->countries()[0]->country->value)->toBe('DK');
});

it('is the repository the container hands out once faked', function () {
    // The point of the trait: everything downstream resolves the contract, so a
    // test swaps the data without touching the code under test.
    $this->fakeGeo(['DK' => ArrayJurisdictionRepository::vat('DK', 'DKK')]);

    expect($this->app->make(JurisdictionRepository::class))
        ->toBeInstanceOf(ArrayJurisdictionRepository::class)
        ->and($this->jurisdiction('DK')?->currency)->toBe('DKK');
});
