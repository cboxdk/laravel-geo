---
title: Installation
weight: 1
description: Install via Composer; the provider and config auto-register.
---

# Installation

```bash
composer require cboxdk/laravel-geo
```

The `GeoServiceProvider` is auto-discovered. It:

- merges `config/geo.php` (only key: `locale`, for display names);
- binds `Contracts\JurisdictionRepository` as a singleton to the addressing-backed
  `AddressingJurisdictionRepository`.

Publish the config if you want to change the reference locale:

```bash
php artisan vendor:publish --tag=geo-config
```

Resolve the repository from the container:

```php
$geo = app(\Cbox\Geo\Contracts\JurisdictionRepository::class);
```
