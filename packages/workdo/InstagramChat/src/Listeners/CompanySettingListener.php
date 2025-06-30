<?php

namespace Workdo\InstagramChat\Listeners;
use App\Events\CompanySettingEvent;

class CompanySettingListener
{
    /**
     * Handle the event.
     */
    public function handle(CompanySettingEvent $event): void
    {
        if(in_array('InstagramChat',$event->html->modules))
        {
            $module = 'InstagramChat';
            $methodName = 'index';
            $controllerClass = "Workdo\\InstagramChat\\Http\\Controllers\\Company\\SettingsController";
            if (class_exists($controllerClass)) {
                $controller = \App::make($controllerClass);
                if (method_exists($controller, $methodName)) {
                    $html = $event->html;
                    $settings = $html->getSettings();
                    $output =  $controller->{$methodName}($settings);
                    $html->add([
                        'html' => $output->toHtml(),
                        'order' => 1110,
                        'module' => $module,
                        'permission' => 'instagram chat manage'
                    ]);
                }
            }
        }
    }
}
