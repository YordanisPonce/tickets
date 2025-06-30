@php
    $instaContact = Workdo\InstagramChat\Entities\InstagramContact::where('ticket_id', $ticket->id)->first();
@endphp
@if ($ticket->type == 'Instagram')
    <img src="{{ isset($instaContact) ? $instaContact->profile_image : '' }}" alt="{{ $ticket->name }}" class="img-fluid">
@endif
