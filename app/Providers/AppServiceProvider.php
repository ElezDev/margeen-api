<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Policies\ClientPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ProductPolicy;
use App\Support\Tenant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);

        Gate::before(function ($user, $ability) {
            if ($user?->isSuperAdmin() && Tenant::isOverride()) {
                return true;
            }

            return null;
        });
    }
}
