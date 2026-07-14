<?php

declare(strict_types=1);

namespace Cbox\Geo\Enums;

/**
 * The broad family of consumption tax a jurisdiction operates. Downstream tax
 * engines key their per-jurisdiction rules off this plus the regime module.
 *
 * `None` is deliberate and load-bearing: a country we have not modelled resolves
 * to `None` rather than being silently assumed taxable — deny-by-default.
 */
enum TaxRegime: string
{
    case Vat = 'vat';
    case Gst = 'gst';
    case SalesTax = 'sales_tax';
    case None = 'none';
}
