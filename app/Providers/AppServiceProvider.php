<?php

namespace App\Providers;

use App\Models\CmsSetting;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configurePilotPreviewSecret();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configurePilotPreviewSecret(): void
    {
        if (config('pilot.preview.secret')) {
            return;
        }

        try {
            if (Schema::hasTable('cms_settings')) {
                $secret = CmsSetting::get('preview_secret');

                if (is_string($secret) && $secret !== '') {
                    config(['pilot.preview.secret' => $secret]);
                }
            }
        } catch (QueryException) {
        }
    }
}
