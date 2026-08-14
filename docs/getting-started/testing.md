---
title: Testing
weight: 2
description: Resolve the real repository in tests — the ISO dataset is deterministic.
---

# Testing

The reference data is deterministic and ships with the package, so tests resolve
the real repository rather than a mock — you get true ISO behaviour for free:

```php
use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\ValueObjects\CountryCode;

$geo = app(JurisdictionRepository::class);

expect($geo->find(new CountryCode('DK'))->currency)->toBe('DKK');
expect($geo->find(new CountryCode('ZZ')))->toBeNull(); // deny-by-default
```

The value objects (`CountryCode`, `SubdivisionCode`) and the `TaxProfiles` map are
pure and need no container at all.

## When the real data gets in the way

Resolving the real repository is right whenever the test is *about* geography. It
gets awkward when the test is about something else and just needs a jurisdiction
of a particular shape — because every case then has to be expressed through a
country that genuinely behaves that way, and inherits everything else true about
that country along with it.

`ArrayJurisdictionRepository` lets a test state the shape directly:

```php
use Cbox\Geo\Testing\ArrayJurisdictionRepository;
use Cbox\Geo\Testing\InteractsWithGeo;   // compose into your TestCase

$this->fakeGeo([
    'DK'    => ArrayJurisdictionRepository::vat('DK', 'DKK'),
    'US-CA' => ArrayJurisdictionRepository::salesTax('US-CA'),  // sub-federal, rooftop
    'XX'    => ArrayJurisdictionRepository::untaxed('XX'),      // levies nothing
]);

$this->jurisdiction('US', 'US-CA')->needsRooftop();   // true
```

Deny-by-default is preserved: anything not registered resolves to `null`, the same
answer the real repository gives for a code it does not recognise. A fake that
invented a country would let a test pass against behaviour production does not
have.

Reach for it when the jurisdiction is scaffolding. Keep the real repository when
the ISO data is the thing under test.
