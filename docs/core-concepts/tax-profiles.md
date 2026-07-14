---
title: Tax profiles
weight: 2
description: The structural tax attributes per jurisdiction — regime, EU membership, sub-federal, rooftop.
---

# Tax profiles

A `TaxProfile` carries the **slow-moving structural facts** about a jurisdiction's
consumption tax — never rates or thresholds, which belong in a tax engine's data
feeds.

| attribute | meaning |
| --- | --- |
| `regime` | `vat` · `gst` · `sales_tax` · `none` |
| `regimeModule` | stable key a tax engine maps to a regime (e.g. `eu-vat`, `us-sales-tax`) |
| `isEuMember` | member of the EU VAT area — drives reverse-charge / OSS routing |
| `isSubFederal` | tax is set below the national level — a subdivision is required |
| `requiresRooftop` | rate resolution needs address-level geocoding |

## Deny-by-default

A country this package has not modelled resolves to `TaxProfile::notModeled()` —
`regime = none`, `regimeModule = null`. Downstream code must treat that as "not
taxable here until modelled", never as a silent default rate.

## Sub-federal vs rooftop

These are deliberately separate flags:

- **Canada** — `isSubFederal = true`, `requiresRooftop = false`. Rates are set at
  province level; a subdivision fully determines them.
- **United States** — `isSubFederal = true`, `requiresRooftop = true`. Rates stack
  below the state (county, city, special district), so address-level geocoding is
  needed; a state alone is not enough.
- **National VAT/GST** (EU, UK, CH, NO, AU, NZ, MX) — both `false`.
