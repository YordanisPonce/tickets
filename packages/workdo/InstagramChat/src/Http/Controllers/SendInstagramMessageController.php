<?php

namespace Workdo\InstagramChat\Http\Controllers;

use App\Http\Controllers\TicketConversionController;
use App\Models\Conversion;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Workdo\InstagramChat\Entities\InstagramContact;

class SendInstagramMessageController extends TicketConversionController
{
    protected $accessToken;

    public function sendMessage(Request $request, $ticket, $user)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'reply_attachments.*' => [
                    'nullable',
                    'file',
                    'mimes:aac,m4a,wav,mp3,mp4,ogg,avi,mov,webm,png,jpeg,gif,pdf,doc,docx,xls,xlsx',
                    function ($attribute, $value, $fail) {
                        if ($value->isValid()) {
                            $maxSize = 0;

                            $extension = $value->getClientOriginalExtension();

                            $sizeInMB = $value->getSize() / 1024 / 1024;

                            // Define max size based on file type
                            $maxSizeLimits = [
                                'aac' => 25,
                                'm4a' => 25,
                                'wav' => 25,
                                'mp3' => 25,
                                'mp4' => 25,
                                'ogg' => 25,
                                'avi' => 25,
                                'mov' => 25,
                                'webm' => 25,
                                'png' => 8,
                                'jpeg' => 8,
                                'jpg' => 8,
                                'gif' => 8,
                            ];

                            if (isset($maxSizeLimits[$extension])) {
                                $maxSize = $maxSizeLimits[$extension];
                            }

                            if ($maxSize > 0 && $sizeInMB > $maxSize) {
                                $fail("The {$attribute} must not be greater than {$maxSize} MB.");
                            }
                        }
                    }
                ],
                'reply_description' => 'required_without:reply_attachments|max:999'
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }
        $instagramContact = InstagramContact::where('ticket_id', $ticket->id)->first();
        if (!$instagramContact) {
            return response()->json(['status' => 0, 'message' => __('Invalid Contact!')]);
        }
        $setting = getCompanyAllSettings();
        $this->accessToken = $setting['instagram_access_token'] ?? null;

        $data = null;
        if ($request->hasfile('reply_attachments')) {
            $errors = [];
            foreach ($request->file('reply_attachments') as $filekey => $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension       = $file->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                $dir        = ('tickets/' . $ticket->ticket_id);
                $path = multipleFileUpload($file, 'reply_attachments', $fileNameToStore, $dir);
                if ($path['flag'] == 1) {
                    $response = $this->sendAttachments($instagramContact->sender_id, $path['url']);
                    if ($response['status'] == 'success') {
                        $conversion = Conversion::create([
                            'ticket_id' => $ticket->id ?? '',
                            'description' => $request->reply_description ?? '',
                            'attachments' => json_encode([$path['url'] ?? '']),
                            'sender' => 1,
                            'is_bookmark' => 0
                        ]);

                        return response()->json([
                            'new_message' => $conversion->description ?? '',
                            'timestamp' => \Carbon\Carbon::parse($conversion->created_at)->format('l h:ia'),
                            'sender_name' => $conversion->replyBy()->name ?? '',
                            'attachments' => json_decode($conversion->attachments) ?? '',
                            'baseUrl'     => env('APP_URL'),
                            'status' => 'success',
                        ]);
                    }
                }
            }
            return response()->json(['status' => 'error', 'message' => 'Failed to send one or more attachments.'], 500);
        }
        $response = $this->sendMessages($instagramContact->sender_id, $request->reply_description);

        if ($response['status'] == 'success') {
            $conversion = Conversion::create([
                'ticket_id' => $ticket->id ?? '',
                'description' => $request->reply_description ?? '',
                'attachments' => $data,
                'sender' => 1,
                'is_bookmark' => 0
            ]);

            return response()->json([
                'new_message' => $conversion->description ?? '',
                'timestamp' => \Carbon\Carbon::parse($conversion->created_at)->format('l h:ia'),
                'sender_name' => $conversion->replyBy()->name ?? '',
                'attachments' => json_decode($conversion->attachments) ?? '',
                'baseUrl'     => env('APP_URL'),
                'status' => 'success',
            ]);
        }
        return response()->json(['status' => 'error', 'message' => 'Failed to send message.'], 500);
    }

    public function sendAttachments($recipient_id, $attachment)
    {

        try {
            $attchment = getFile($attachment);
            $response = Http::post("https://graph.facebook.com/v21.0/me/messages", [
                'recipient' => [
                    'id' => $recipient_id,
                ],
                'message' => [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => $attchment,
                            'is_reusable' => true
                        ]
                    ]
                ],
                'messaging_type' => 'RESPONSE',
                'access_token' => $this->accessToken
            ]);

            return ['status' => 'success', 'data' => $response->json()];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function sendMessages($recipient_id, $message)
    {
        // prepare request data
        $jsonData = [
            'message' => [
                'text' => $message,
            ],
            'recipient' => [
                'id' => $recipient_id,
            ],
            'access_token' => $this->accessToken
        ];
        try {
            //  api call to send message
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://graph.facebook.com/v21.0/me/messages', $jsonData);
            return ['status' => 'success', 'data' => $response->json()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'data' => 'Attachment upload failed: ' . $e->getMessage()];
        }
    }
}
