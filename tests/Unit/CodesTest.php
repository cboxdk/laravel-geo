<?php

declare(strict_types=1);

use Cbox\Geo\Exceptions\InvalidCountryCode;
use Cbox\Geo\Exceptions\InvalidSubdivisionCode;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\SubdivisionCode;

it('normalises a country code to upper case', function () {
    expect((new CountryCode('dk'))->value)->toBe('DK')
        ->and((string) new CountryCode(' us '))->toBe('US');
});

it('rejects a malformed country code', function () {
    new CountryCode('Denmark');
})->throws(InvalidCountryCode::class);

it('parses a subdivision code into its country and part', function () {
    $code = new SubdivisionCode('us-ca');

    expect($code->value)->toBe('US-CA')
        ->and($code->country->value)->toBe('US')
        ->and($code->code)->toBe('CA');
});

it('rejects a malformed subdivision code', function () {
    new SubdivisionCode('US_CA');
})->throws(InvalidSubdivisionCode::class);
