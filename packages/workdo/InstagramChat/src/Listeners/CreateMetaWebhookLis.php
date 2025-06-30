<?php

namespace Workdo\InstagramChat\Listeners;

use App\Events\CreateMetaWebhook;
use App\Events\CreateTicket;
use App\Models\Conversion;
use App\Models\Settings;
use App\Models\Ticket;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Pusher\Pusher;
use Workdo\InstagramChat\Entities\InstagramContact;

class CreateMetaWebhookLis
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


    public function handle(CreateMetaWebhook $event)
    {
        if (moduleIsActive('InstagramChat')) {
            $payload = $event->payload;


            if (isset($payload['entry'])) {
                $entries = $payload['entry']; // Standard format
            } else {
                $entries = $payload; // Alternative format (indexed array)
            }
            if ($payload['object'] == 'instagram') {

                foreach ($entries as $entry) {
                    if (!isset($entry['id']) || empty($entry['messaging'])) {
                        continue;
                    }
                    $requestClientId = $entry['id'];
                    foreach ($entry['messaging'] as $messaging) {
                        $this->processMessage($messaging, $requestClientId);
                    }
                }
            }
        }
    }
    private function processMessage(array $messaging, string $requestClientId)
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $recipientId = $messaging['recipient']['id'] ?? null;
        $messageText = $messaging['message']['text'] ?? null;
        $attachments = $messaging['message']['attachments'] ?? null;

        if (!$senderId || !$requestClientId) {
            return;
        }

        $setting = Settings::where('name', 'instagram_client_id')
            ->where('value', $requestClientId)
            ->first();

        if (!$setting) {
            return;
        }
        $tokenSetting = company_setting('instagram_access_token', $setting->created_by);

        if (!$tokenSetting) {
            return;
        }

        $isOutGoing = ($senderId == $requestClientId);
        if ($isOutGoing) {
            return;
        }
        $instagramContact = InstagramContact::where([
            ['sender_id', $isOutGoing ? $recipientId : $senderId],
            ['created_by', $setting->created_by],
        ])->first();

        if (empty($instagramContact)) {
            $profileDetail = $this->getInstagramProfileDetails($senderId, $tokenSetting);
            $ticket = new Ticket();
            $ticket->ticket_id = time();
            $ticket->name = $profileDetail['data']['username'] ?? '';
            $ticket->description = $messageText ?? '';
            $ticket->attachments = json_encode($attachments) ?? '';
            $ticket->type = 'Instagram';
            $ticket->status = 'In Progress';
            $ticket->created_by = '1';
            $ticket->save();
            $instagramContact = InstagramContact::create([
                'ticket_id' => $ticket->id ?? '',
                'name' => $profileDetail['status'] == 1 ? $profileDetail['data']['name'] : null,
                'profile_image' => $profileDetail['status'] == 1 ? $profileDetail['data']['profile_pic'] : null,
                'user_name' => $profileDetail['status'] == 1 ? $profileDetail['data']['username'] : null,
                'sender_id' => $senderId,
                'created_by' => $setting->created_by,
            ]);
            $settings = getCompanyAllSettings();
            // Manage Pusher
            if (
                isset($settings['CHAT_MODULE']) && $settings['CHAT_MODULE'] == 'yes' &&
                isset($settings['PUSHER_APP_KEY'], $settings['PUSHER_APP_CLUSTER'], $settings['PUSHER_APP_ID'], $settings['PUSHER_APP_SECRET']) &&
                !empty($settings['PUSHER_APP_KEY']) &&
                !empty($settings['PUSHER_APP_CLUSTER']) &&
                !empty($settings['PUSHER_APP_ID']) &&
                !empty($settings['PUSHER_APP_SECRET'])
            ) {
                $options = array(
                    'cluster' => $settings['PUSHER_APP_CLUSTER'],
                    'useTLS' => true,
                );
    
                $pusher = new Pusher(
                    $settings['PUSHER_APP_KEY'],
                    $settings['PUSHER_APP_SECRET'],
                    $settings['PUSHER_APP_ID'],
                    $options
                );
    
                $data = [
                    'id'        => $ticket->id,
                    'tikcet_id' => $ticket->ticket_id,
                    'name'      => $ticket->name,
                    'subject'   => $ticket->subject,
                    'status'    => $ticket->status,
                    'created_at' => $ticket->created_at->diffForHumans(),
                    'latestMessage' => $ticket->latestMessages($ticket->id),
                    'unreadMessge' => $ticket->unreadMessge($ticket->id)->count(),
                    'type' => $ticket->type,
                ];
    
                $channel = "new-ticket-1";
                $event = "new-ticket-event-1";
                $pusher->trigger($channel, $event, $data);
            }
        }

        if ($messageText) {
            $instagramChat = Conversion::create([
                'ticket_id' => $instagramContact->ticket_id ?? '',
                'description' => $messageText ?? '',
                'attachments' => null,
                'sender' => 'user',
                'is_bookmark' => 0
            ]);
            $this->broadcastMessage($instagramContact, $instagramChat, $isOutGoing);
            $ticket = Ticket::find($instagramContact->ticket_id);
            event(new CreateTicket($ticket, $instagramChat));
        }


        // handle attachment
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (!isset($attachment['type']) || !isset($attachment['payload']['url'])) {
                    continue;
                }

                $attachmentUrl = $attachment['payload']['url'];

                $this->handleImageAttachment($attachmentUrl, $instagramContact, $isOutGoing, $instagramContact->ticket_id);
            }
        }
    }

    private function getInstagramProfileDetails($senderId, $token)
    {
        $url = "https://graph.facebook.com/{$senderId}?fields=name,profile_pic,username&access_token={$token}";
        $response = Http::get($url);
        if ($response->successful()) {
            $data = $response->json();
            return ['status' => 1, 'data' => $data];
        } else {
            return ['status' => 0];
        }
    }

    private function handleImageAttachment($attachmentUrl, $instagramContact, $isOutGoing, $ticket_id)
    {
        $fileContents = Http::get($attachmentUrl);

        if ($fileContents->successful()) {
            $fileExtension = pathinfo($attachmentUrl, PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExtension;

            $temporaryFile = UploadedFile::fake()->createWithContent($fileName, $fileContents->body());

            $path = "instagram_files";
            $request = new \Illuminate\Http\Request();
            $request->files->set('attachment', $temporaryFile);

            $uploadResult = uploadFile($request, 'attachment', $fileName, $path, ['max:10000']);

            if ($uploadResult['flag'] == 1) {
                $attachmentUrl = $uploadResult['url'];
                $instagramChat = Conversion::create([
                    'ticket_id' => $ticket_id,
                    'sender' => 'user',
                    'description' => null,
                    'attachments' => isset($attachmentUrl) ? json_encode([$attachmentUrl]) : null,
                    'is_bookmark' => 0
                ]);


                // Broadcast the message (with the image path)
                $this->broadcastMessage($instagramContact, $instagramChat, $isOutGoing);
            }
        }
    }

    private function broadcastMessage($instagramContact, $instagramChat, $isOutGoing)
    {
        $ticket = Ticket::find($instagramChat->ticket_id);

        $settings = getCompanyAllSettings();
        // **Pusher Notifications**
        if (
            isset($settings['CHAT_MODULE']) && $settings['CHAT_MODULE'] == 'yes' &&
            isset($settings['PUSHER_APP_KEY'], $settings['PUSHER_APP_CLUSTER'], $settings['PUSHER_APP_ID'], $settings['PUSHER_APP_SECRET']) &&
            !empty($settings['PUSHER_APP_KEY']) &&
            !empty($settings['PUSHER_APP_CLUSTER']) &&
            !empty($settings['PUSHER_APP_ID']) &&
            !empty($settings['PUSHER_APP_SECRET'])
        ) {
            $options = array(
                'cluster' => $settings['PUSHER_APP_CLUSTER'],
                'useTLS' => true,
            );

            $pusher = new Pusher(
                $settings['PUSHER_APP_KEY'],
                $settings['PUSHER_APP_SECRET'],
                $settings['PUSHER_APP_ID'],
                $options
            );
            $data = [
                'id'        => $instagramChat->id,
                'tikcet_id' => $instagramChat->ticket_id,
                'ticket_unique_id' => $ticket->id,
                'new_message' => $instagramChat->description,
                'timestamp'   => \Carbon\Carbon::parse($instagramChat->created_at)->format('l h:ia'),
                'sender_name' => $instagramChat->replyBy()->name,
                'attachments' => json_decode($instagramChat->attachments),
                'baseUrl'     => env('APP_URL'),
                'latestMessage' => $ticket->latestMessages($ticket->id),
                'unreadMessge' => $ticket->unreadMessge($ticket->id)->count(),
                'profile_img' => $instagramContact->profile_image ?? ''
            ];

            if ($ticket->is_assign == null) {
                $channel = "ticket-reply-$ticket->created_by";
                $event = "ticket-reply-event-$ticket->created_by";
            } else {
                $channel = "ticket-reply-$ticket->is_assign";
                $event = "ticket-reply-event-$ticket->is_assign";
            }
            
            $pusher->trigger($channel, $event, $data);
        }
    }
}
