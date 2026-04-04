<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AdminUser;
use App\Models\Resident;
use App\Models\Vendor;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Repositories\Contracts\TroubleCategoryRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Repositories\PropertyRepository;
use App\Repositories\ResidentRepository;
use App\Repositories\TroubleCategoryRepository;
use App\Repositories\VendorRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PropertyRepositoryInterface::class, PropertyRepository::class);
        $this->app->singleton(ResidentRepositoryInterface::class, ResidentRepository::class);
        $this->app->singleton(VendorRepositoryInterface::class, VendorRepository::class);
        $this->app->singleton(TroubleCategoryRepositoryInterface::class, TroubleCategoryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // notification_logs.recipient_type を MorphTo で解決するための別名
        Relation::morphMap([
            'resident' => Resident::class,
            'vendor' => Vendor::class,
            'admin' => AdminUser::class,
        ]);
    }
}
