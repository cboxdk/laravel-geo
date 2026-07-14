---
title: Overview
weight: 0
description: Canonical ISO country/subdivision/currency reference with tax-relevant attributes, bound to stable codes.
---

# Cbox Geo

`cboxdk/laravel-geo` is the shared **jurisdiction primitive** for the Cbox
portfolio. It resolves countries, subdivisions and currencies from vetted ISO
reference data, and layers on the **tax profile** of each jurisdiction — the
structural facts (which tax regime, EU membership, whether it is sub-federal,
whether it needs rooftop geocoding) that billing and tax engines build on.

Everything binds to **stable ISO codes** — ISO 3166-1 alpha-2 countries, ISO
3166-2 subdivisions, ISO 4217 currencies — so nothing downstream matches on free
text, and unknown input is denied rather than guessed.

## Mental model

- A **country** or a **country + subdivision** resolves to a `Jurisdiction`.
- A `Jurisdiction` carries its ISO geography (name, currency) plus a `TaxProfile`.
- Resolution is **deny-by-default**: unknown → `null`.

## Sections

- [Getting started](getting-started/_index.md) — install, wire, test.
- [Core concepts](core-concepts/_index.md) — jurisdictions and tax profiles.
- [Extension points](extension-points/_index.md) — swapping the data source.
