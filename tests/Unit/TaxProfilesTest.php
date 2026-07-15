<?php

declare(strict_types=1);

use Cbox\Geo\Enums\TaxRegime;
use Cbox\Geo\Support\TaxProfiles;
use Cbox\Geo\ValueObjects\CountryCode;

it('marks EU members with the eu-vat regime', function () {
    $dk = TaxProfiles::for(new CountryCode('DK'));

    expect($dk->regime)->toBe(TaxRegime::Vat)
        ->and($dk->regimeModule)->toBe('eu-vat')
        ->and($dk->isEuMember)->toBeTrue()
        ->and($dk->isSubFederal)->toBeFalse();
});

it('treats the UK as a non-EU national VAT regime', function () {
    $gb = TaxProfiles::for(new CountryCode('GB'));

    expect($gb->regimeModule)->toBe('uk-vat')
        ->and($gb->isEuMember)->toBeFalse()
        ->and($gb->isSubFederal)->toBeFalse();
});

it('marks the US as sub-federal and rooftop-requiring', function () {
    $us = TaxProfiles::for(new CountryCode('US'));

    expect($us->regime)->toBe(TaxRegime::SalesTax)
        ->and($us->isSubFederal)->toBeTrue()
        ->and($us->requiresRooftop)->toBeTrue();
});

it('marks Canada as sub-federal but not rooftop-requiring', function () {
    $ca = TaxProfiles::for(new CountryCode('CA'));

    expect($ca->regime)->toBe(TaxRegime::Gst)
        ->and($ca->isSubFederal)->toBeTrue()
        ->and($ca->requiresRooftop)->toBeFalse();
});

it('models India as a GST regime with a dedicated module', function () {
    $in = TaxProfiles::for(new CountryCode('IN'));

    expect($in->regime)->toBe(TaxRegime::Gst)
        ->and($in->regimeModule)->toBe('in-gst')
        ->and($in->isSubFederal)->toBeFalse();
});

it('models Singapore as a national GST regime', function () {
    $sg = TaxProfiles::for(new CountryCode('SG'));

    expect($sg->regime)->toBe(TaxRegime::Gst)
        ->and($sg->regimeModule)->toBe('sg-gst');
});

it('denies by default for an unmodelled country', function () {
    $profile = TaxProfiles::for(new CountryCode('ZZ'));

    expect($profile->regime)->toBe(TaxRegime::None)
        ->and($profile->isModeled())->toBeFalse()
        ->and($profile->regimeModule)->toBeNull();
});
