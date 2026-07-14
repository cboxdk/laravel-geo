---
title: Requirements
weight: 2
description: PHP and Laravel versions and the direct dependencies the resolver enforces.
---

# Requirements

From `composer.json`:

- **PHP** `^8.4`
- **Laravel** `^12 || ^13` (`illuminate/contracts`, `illuminate/support`)
- **[`commerceguys/addressing`](https://github.com/commerceguys/addressing)** `^2.2`
  — the vetted ISO 3166 / ISO 4217 reference dataset this package wraps.

No database migration is required: the reference data ships inside the addressing
library. Persisting jurisdictions for foreign-key integrity is an optional host
concern.
