<?php

namespace Workdo\InstagramChat\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as Provider;
use App\Events\CompanyMenuEvent;
use App\Events\CompanySettingEvent;
use App\Events\CompanySettingMenuEvent;
use App\Events\CreateMetaWebhook;
use App\Events\DestroyTicket;
use Workdo\InstagramChat\Listeners\CreateMetaWebhookLis;
use Workdo\InstagramChat\Listeners\CompanyMenuListener;
use Workdo\InstagramChat\Listeners\CompanySettingListener;
use Workdo\InstagramChat\Listeners\CompanySettingMenuListener;
use Workdo\InstagramChat\Listeners\DestroyTicketLis;

class EventServiceProvider extends Provider
{
    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    protected $listen = [
        CompanySettingEvent::class => [
            CompanySettingListener::class,
        ],
        CompanySettingMenuEvent::class => [
            CompanySettingMenuListener::class,
        ],
        DestroyTicket::class => [
            DestroyTicketLis::class,
        ],
        CreateMetaWebhook::class => [
            CreateMetaWebhookLis::class,
        ]
    ];

    /**
     * Get the listener directories that should be used to discover events.
     *
     * @return array
     */
    protected function discoverEventsWithin()
    {
        return [
            __DIR__ . '/../Listeners',
        ];
    }
}
