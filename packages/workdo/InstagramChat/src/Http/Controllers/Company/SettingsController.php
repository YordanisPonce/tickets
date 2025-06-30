<?php
// This file use for handle company setting page

namespace Workdo\InstagramChat\Http\Controllers\Company;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($settings)
    {
        return view('instagram-chat::company.settings.index',compact('settings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeInsta(Request $request)
    {
        if (Auth::user()->isAbleTo('instagram chat manage')) {

        $validator = Validator::make(
            $request->all(),
            [
                'instagram_client_id' => 'required',
                'instagram_access_token' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $post = $request->all();
            unset($post['_token']);
        foreach ($post as $key => $value) {
            $data = [
                'name' => $key,
                'created_by' => creatorId(),
            ];

            Settings::updateOrInsert($data, ['value' => $value]);
        }

        companySettingCacheForget(creatorId());

        return redirect()->back()->with('success', __('Instagram Chat Settings Save Successfully!'));
    } else {
        return redirect()->back()->with('error', __('Permission Denied.'));
    }
    }
}
