<?php

namespace App\Providers;

use App\Domains\Carriers\Contracts\CarrierInterface;
use App\Domains\Carriers\Contracts\CarrierRegistryInterface;
use App\Domains\Carriers\Services\CarrierRegistry;
use App\Domains\Carriers\Services\CdekCarrier;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([CdekCarrier::class], 'carriers');

        $this->app->singleton(CarrierRegistryInterface::class, CarrierRegistry::class);

        $this->app->bind(CarrierInterface::class, function (Application $app): CarrierInterface {
            return $app->make(CarrierRegistryInterface::class)->for((string) config('carriers.default'));
        });
    }
}
