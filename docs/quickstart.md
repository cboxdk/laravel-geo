---
title: Quickstart
weight: 1
description: Resolve a jurisdiction and read its currency and tax profile in one read.
---

# Quickstart

Resolve the container-bound repository and look up a jurisdiction:

```php
use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\SubdivisionCode;

$geo = app(JurisdictionRepository::class);

// A national VAT regime.
$dk = $geo->find(new CountryCode('DK'));
$dk->currency;                    // "DKK"
$dk->taxProfile->regimeModule;    // "eu-vat"
$dk->isResolvedForTax();          // true

// A sub-federal regime needs a subdivision.
$us = $geo->find(new CountryCode('US'));
$us->needsSubdivision();          // true
$us->taxProfile->requiresRooftop; // true

$california = $geo->find(new CountryCode('US'), new SubdivisionCode('US-CA'));
$california->subdivisionName;     // "California"

// Unknown input is denied, never guessed.
$geo->find(new CountryCode('ZZ')); // null
```

Build data-driven selects instead of free-text country/state fields:

```php
$countries = $geo->countries();               // list<Jurisdiction>
$provinces = $geo->subdivisions(new CountryCode('CA')); // list<Subdivision>, ISO-coded
```
