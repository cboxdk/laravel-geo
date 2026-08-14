<?php

declare(strict_types=1);

namespace Cbox\Geo\Tests;

use Cbox\Geo\GeoServiceProvider;
use Cbox\Geo\Testing\InteractsWithGeo;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithGeo;

    /**
     * @return list<class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [GeoServiceProvider::class];
    }
}
