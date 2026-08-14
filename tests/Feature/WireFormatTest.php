<?php

declare(strict_types=1);

use Cbox\Geo\Testing\ArrayJurisdictionRepository;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\LocalityCode;
use Cbox\Geo\ValueObjects\SubdivisionCode;

// Encoding an object of public readonly properties Just Works, which is the
// problem: the wrapper these classes exist to provide leaked into every payload
// they were encoded into. `{"country":{"value":"DK"}}` is nobody's idea of an API,
// and because it worked, that shape would have become one by accident rather than
// by decision — and undoing it later is somebody else's broken integration.

it('encodes a country code as the code itself', function () {
    expect(json_encode(new CountryCode('DK')))->toBe('"DK"');
});

it('encodes a subdivision code as its ISO 3166-2 form', function () {
    // Nothing is lost: country and code are both derived from this string on the
    // way back in, so the full object round-trips through the constructor.
    $encoded = json_encode(new SubdivisionCode('US-CA'));

    expect($encoded)->toBe('"US-CA"')
        ->and(new SubdivisionCode(json_decode($encoded))->country->value)->toBe('US');
});

it('keeps a locality an object, because it genuinely is one', function () {
    // A locality is a scheme AND a key AND its subdivision — flattening it to a
    // string would lose which coding system the key belongs to. What it must not
    // do is nest a wrapped subdivision inside itself.
    $decoded = json_decode(
        (string) json_encode(new LocalityCode(new SubdivisionCode('US-KS'), 'sst-fips', '36000')),
        true,
    );

    expect($decoded['subdivision'])->toBe('US-KS')
        ->and($decoded['scheme'])->toBe('sst-fips')
        ->and($decoded['value'])->toBe('36000');
});

it('carries the flat codes through a whole jurisdiction payload', function () {
    // The shape an API response actually contains — this is the regression that
    // matters, not the codes in isolation.
    $decoded = json_decode((string) json_encode(ArrayJurisdictionRepository::salesTax('US-CA')), true);

    expect($decoded['country'])->toBe('US')
        ->and($decoded['subdivision'])->toBe('US-CA');
});
