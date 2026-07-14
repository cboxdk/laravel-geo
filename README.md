# Cbox Geo

**`cboxdk/laravel-geo`** — a canonical geography reference for Laravel: countries,
subdivisions and currencies bound to **stable ISO codes**, resolved deny-by-default,
and carrying the **tax-relevant attributes** that billing and tax engines build on.
A dependency-light framework library — every capability sits behind a contract you
bind, mock or replace.

> This is the shared **jurisdiction primitive** for the Cbox portfolio. Identity
> (org addresses), billing (invoice address, seller entities) and the tax engine
> all bind to the *same* jurisdiction keys, so nothing downstream matches on free
> text.

## Why it exists

A tax or billing engine must never fuzzy-match a country name or guess a rate from
a ZIP. Everything has to bind to a stable, validated jurisdiction key. This package
provides that key — ISO 3166-1 alpha-2 countries, ISO 3166-2 subdivisions, ISO 4217
currencies — sourced from the vetted [`commerceguys/addressing`][addr] dataset, with
one layer added on top: the **tax profile** of each jurisdiction.

```php
use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\SubdivisionCode;

$geo = app(JurisdictionRepository::class);

$dk = $geo->find(new CountryCode('DK'));
$dk->currency;                      // "DKK"
$dk->taxProfile->regimeModule;      // "eu-vat"
$dk->taxProfile->isEuMember;        // true
$dk->isResolvedForTax();            // true — national regime

$us = $geo->find(new CountryCode('US'));
$us->needsSubdivision();            // true — sub-federal, a state is required
$us->taxProfile->requiresRooftop;   // true — rooftop geocoding needed for rates

$ca = $geo->find(new CountryCode('US'), new SubdivisionCode('US-CA'));
$ca->subdivisionName;               // "California"

$geo->find(new CountryCode('ZZ'));  // null — deny-by-default, never a guess
```

## The tax profile

Each modelled jurisdiction carries structural, slow-moving facts — **not rates**
(those live in the tax engine's data feeds):

| attribute | meaning |
| --- | --- |
| `regime` | `vat` · `gst` · `sales_tax` · `none` (unmodelled → deny-by-default) |
| `regimeModule` | stable key a tax engine maps to a regime (`eu-vat`, `us-sales-tax`, …) |
| `isEuMember` | drives EU reverse-charge / OSS routing |
| `isSubFederal` | tax is set below the national level — a subdivision is required (US, CA) |
| `requiresRooftop` | rate resolution needs address-level geocoding (US only) |

Sub-federal is deliberately split from rooftop: **Canada** stacks at province level
(a subdivision is enough), while the **US** stacks below the state and needs rooftop
geocoding. National VAT/GST regimes (EU, UK, CH, NO, AU, NZ, MX) need neither.

## Design

- **Contracts-first.** Depend on `Contracts\JurisdictionRepository`; the default
  binding wraps the ISO dataset. Swap or persist the data without touching callers.
- **No forced migration.** The reference data ships inside the library; persisting
  jurisdictions for FK integrity is an optional host concern.
- **Deny-by-default.** An unknown country, an unknown subdivision, or a subdivision
  that does not belong to its country all resolve to `null`.

## Requirements

PHP `^8.4`; Laravel `^12 || ^13`. See `composer.json`.

## Development

```bash
composer install
composer qa    # pint --test, phpstan (level max), pest, license-check, audit
```

## License

MIT.

[addr]: https://github.com/commerceguys/addressing
