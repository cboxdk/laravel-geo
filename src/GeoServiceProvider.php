<?php

declare(strict_types=1);

namespace Cbox\Geo;

use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\Repositories\AddressingJurisdictionRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Package entry point. Binds the jurisdiction repository to the addressing-backed
 * implementation and merges/publishes config. The ISO reference data ships inside
 * the addressing library, so no migration is forced on the host; persistence is
 * an optional host concern.
 */
class GeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/geo.php', 'geo');

        $this->app->singleton(JurisdictionRepository::class, static function (Application $app): AddressingJurisdictionRepository {
            $locale = $app->make(Config::class)->get('geo.locale', 'en');

            return new AddressingJurisdictionRepository(
                new CountryRepository,
                new SubdivisionRepository,
                is_string($locale) ? $locale : 'en',
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/geo.php' => $this->app->configPath('geo.php'),
            ], 'geo-config');
        }
    }
}
