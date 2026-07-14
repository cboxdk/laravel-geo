---
title: Jurisdictions
weight: 1
description: The resolved country/subdivision key, bound to ISO codes and deny-by-default.
---

# Jurisdictions

A `Jurisdiction` is a country — optionally narrowed to a subdivision — resolved
from ISO reference data. It is the **join key** the rest of the system binds to:
seller-entity tax registrations, buyer addresses, tax-rate sources, and regime
modules all reference the same jurisdiction.

```php
$j = $geo->find(new CountryCode('US'), new SubdivisionCode('US-CA'));

$j->country->value;      // "US"
$j->subdivision->value;  // "US-CA"
$j->currency;            // "USD"
$j->taxProfile;          // TaxProfile
```

## Codes, not free text

`CountryCode` (ISO 3166-1 alpha-2) and `SubdivisionCode` (ISO 3166-2) validate
their *shape* on construction and normalise to upper case. A `SubdivisionCode`
always carries its parent country, so a state can never be attached to the wrong
country silently.

## Resolution is deny-by-default

`find()` returns `null` for an unknown country, an unknown subdivision, or a
subdivision that does not belong to the country it is resolved against. It never
returns a best-guess match — a wrong jurisdiction means a wrong tax outcome.

## Sub-federal resolution

`needsSubdivision()` is `true` when a country sets tax below the national level
(US, Canada) but no subdivision was supplied. `isResolvedForTax()` tells a caller
whether the jurisdiction is specified finely enough to act on.
