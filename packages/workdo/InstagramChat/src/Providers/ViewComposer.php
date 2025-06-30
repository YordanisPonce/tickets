<?php

namespace Workdo\InstagramChat\Providers;

use Illuminate\Support\ServiceProvider;

class ViewComposer extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer(['admin.chats.new-chat', 'admin.chats.new-chat-messge'], function ($view) {
            if (moduleIsActive('InstagramChat')) {
                $view->with('isInstagramChat', true);
            }
        });
    }
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
