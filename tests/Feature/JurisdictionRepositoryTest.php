<?php

declare(strict_types=1);

use Cbox\Geo\Contracts\JurisdictionRepository;
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

it('denies a subdivision that belongs to a different country', function () {
    expect($this->repo->find(new CountryCode('US'), new SubdivisionCode('CA-QC')))->toBeNull();
});

it('lists subdivisions bound to ISO codes for selects', function () {
    $provinces = $this->repo->subdivisions(new CountryCode('CA'));

    $codes = array_map(fn ($s) => $s->code->value, $provinces);

    expect($codes)->toContain('CA-QC', 'CA-ON');
});

it('enumerates countries for a select', function () {
    $countries = $this->repo->countries();

    $codes = array_map(fn ($j) => $j->country->value, $countries);

    expect($codes)->toContain('DK', 'US', 'GB', 'AU');
});
