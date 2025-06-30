<?php

namespace Workdo\InstagramChat\Listeners;

use App\Models\Conversion;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Workdo\InstagramChat\Entities\InstagramContact;

class DestroyTicketLis
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function handle($event)
    {
        $ticket = $event->ticket;
        if($ticket->type == 'Instagram'){
            $instagram = InstagramContact::where('ticket_id',$ticket->id)->first();
            $instagram->delete();
        }
    }
}
