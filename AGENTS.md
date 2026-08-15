# You are in `laravel-geo`

Canonical **jurisdiction reference data** — countries, subdivisions, localities — as
typed value objects. Everything above it resolves a place through this rather than
matching country names as strings.

**It knows nothing about tax.** No rates, no rules, no thresholds. That separation is
why a supply can be assessed as a function of `(seller registrations, buyer
jurisdiction, product type)` instead of a fuzzy string comparison.

## What is easy to get wrong here

**`LocalityCode` is a typed carrier, not a resolver.** It holds a `scheme` naming a
coding system and an opaque `value` in it, because US sub-state jurisdictions are
keyed differently per state — ZIP+4 through a boundary index, a county name, a
coordinate, a comptroller authority code. This package ships no rooftop reference
data; a host binds a geocoder that populates it.

**Deny-by-default applies here too.** An unresolved jurisdiction returns null and the
caller refuses. It never falls back to a country when a subdivision was required.

**Nothing here is a tax decision.** If a change starts to look like one — "this
territory is outside the VAT area" — it belongs in `laravel-tax`, which already
models EU territories.

**Live branch: `main`.** A hardcoded `version` in `composer.json` once made Packagist
silently skip tags; never add one back.

## The gate

`vendor/bin/pint --test` · `vendor/bin/phpstan analyse --memory-limit=1G` (level max) ·
`vendor/bin/pest`

The platform overview lives at `laravel-tax/PLATFORM.md`.
