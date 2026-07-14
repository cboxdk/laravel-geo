---
title: Custom repository
weight: 1
description: Bind your own JurisdictionRepository to persist or extend the data.
---

# Custom repository

The default `AddressingJurisdictionRepository` reads the ISO dataset shipped in
`commerceguys/addressing`. To persist jurisdictions in your own tables (for FK
integrity), extend coverage, or source data elsewhere, bind your own
implementation of the contract:

```php
use Cbox\Geo\Contracts\JurisdictionRepository;

$this->app->singleton(JurisdictionRepository::class, function ($app) {
    return new DatabaseJurisdictionRepository(/* ... */);
});
```

Two rules any implementation must keep:

- **Bind to ISO codes.** Return `Jurisdiction`/`Subdivision` value objects keyed by
  ISO 3166 codes, so callers stay consistent across data sources.
- **Deny-by-default.** Return `null` for anything you cannot resolve exactly; never
  a best-guess match.

The `TaxProfiles` map is the canonical tax-attribute layer — reuse it via
`TaxProfiles::for($countryCode)` rather than re-deriving profiles, so every data
source agrees on which jurisdictions are EU / sub-federal / rooftop.
