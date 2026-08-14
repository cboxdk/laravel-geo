<?php

declare(strict_types=1);

namespace Cbox\Geo\Tests\Fixtures;

use Cbox\Geo\Testing\ArrayJurisdictionRepository;
use Cbox\Geo\Testing\InteractsWithGeo;
use Cbox\Geo\ValueObjects\Jurisdiction;
use Orchestra\Testbench\TestCase;

/**
 * A PHPStan-visible composition site for {@see InteractsWithGeo} (Pest test
 * closures are not analysed), so the trait and its helpers are type-checked.
 */
class GeoFixture extends TestCase
{
    use InteractsWithGeo;

    public function build(): ?Jurisdiction
    {
        $this->fakeGeo([
            'DK' => ArrayJurisdictionRepository::vat('DK', 'DKK'),
            'US-CA' => ArrayJurisdictionRepository::salesTax('US-CA'),
            'XX' => ArrayJurisdictionRepository::untaxed('XX'),
        ]);

        return $this->jurisdiction('US', 'US-CA');
    }
}
