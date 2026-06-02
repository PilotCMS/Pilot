<?php

namespace Tweaker;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class TweakerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tweaker.php', 'tweaker');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/tweaker.php' => config_path('tweaker.php'),
        ], 'tweaker-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Blade::directive('tweakerScripts', function () {
            return "<?php if (config('tweaker.enabled')) { echo '<script src=\"'.route('tweaker.script').'\"></script>'; } ?>";
        });
    }
}
