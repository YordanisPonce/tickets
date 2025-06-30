<?php

namespace Workdo\InstagramChat\Listeners;

use App\Events\CompanySettingMenuEvent;

class CompanySettingMenuListener
{
    /**
     * Handle the event.
     */
    public function handle(CompanySettingMenuEvent $event): void
    {
        $module = 'InstagramChat';
        $menu = $event->menu;
        $menu->add([
            'title' => __('Instagram Chat Settings'),
            'name' => 'instagramchat',
            'order' => 1110,
            'ignore_if' => [],
            'depend_on' => [],
            'route' => 'home',
            'navigation' => 'instagram-sidenav',
            'module' => $module,
            'permission' => 'instagram chat manage'
        ]);
    }
}
