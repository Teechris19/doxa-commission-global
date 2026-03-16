<?php

namespace App\Providers;

use App\Models\PartnershipCategory;
use App\Models\PartnershipIntent;
use App\Policies\PartnershipCategoryPolicy;
use App\Policies\PartnershipIntentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(PartnershipIntent::class, PartnershipIntentPolicy::class);
        Gate::policy(PartnershipCategory::class, PartnershipCategoryPolicy::class);
    }
}
