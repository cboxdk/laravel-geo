<?php

declare(strict_types=1);

namespace Cbox\Geo\Testing;

use Cbox\Geo\Contracts\JurisdictionRepository;
use Cbox\Geo\ValueObjects\CountryCode;
use Cbox\Geo\ValueObjects\Jurisdiction;
use Cbox\Geo\ValueObjects\SubdivisionCode;
use Illuminate\Container\Container;

/**
 * Test helper: build jurisdictions without ceremony, and swap the repository for
 * an in-memory one. Dogfooded by this package's own suite — if a fake is awkward
 * here, fix the fake.
 */
trait InteractsWithGeo
{
    /**
     * Bind an in-memory repository holding exactly the jurisdictions given.
     *
     * @param  array<string, Jurisdiction>  $jurisdictions  keyed "DK" or "US-CA"
     */
    protected function fakeGeo(array $jurisdictions): ArrayJurisdictionRepository
    {
        $repository = new ArrayJurisdictionRepository($jurisdictions);

        // The `app()` helper rather than `$this->app`, which Testbench types as
        // nullable — the trait is composed into test cases it does not control, and
        // should not force each one to prove the container is booted.
        Container::getInstance()->instance(JurisdictionRepository::class, $repository);

        return $repository;
    }

    /** Resolve a jurisdiction from whichever repository is currently bound. */
    protected function jurisdiction(string $country, ?string $subdivision = null): ?Jurisdiction
    {
        $repository = Container::getInstance()->make(JurisdictionRepository::class);

        return $repository->find(
            new CountryCode($country),
            $subdivision === null ? null : new SubdivisionCode($subdivision),
        );
    }
}
