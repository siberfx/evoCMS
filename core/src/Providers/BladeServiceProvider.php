<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $directives = $this->app['config']->get('view.directive');
        if (\is_array($directives)) {
            foreach ($directives as $name => $callback) {
                $this->app->get('blade.compiler')->directive($name, $callback);
            }
        }

        Blade::if('auth', function (string $context = 'web') {
            return evo()->getLoginUserID($context) !== false;
        });

        Blade::if('guest', function (string $context = 'web') {
            return evo()->getLoginUserID($context) === false;
        });
    }
}
