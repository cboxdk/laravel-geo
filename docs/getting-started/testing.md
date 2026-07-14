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
