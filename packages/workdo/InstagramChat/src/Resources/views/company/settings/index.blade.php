<div class="card" id="instagram-sidenav">
    <form method="post" class="needs-validation" novalidate action="{{ route('instagram.setting.store') }}" enctype="multipart/form-data">
        @csrf
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-sm-10 col-9">
                <h5 class="">{{ __('Instagram Chat Settings') }}</h5>
                <small>{{ __('Edit your Instagram Chat Setting') }}</small>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group" id="StoreLink" style="display: block">
                    <label for="store_link" class="form-label">{{__('Instagram Chat Webhook URL')}}</label>
                    <div class="input-group gap-2">
                        <input type="text" value="{{route('meta.callback')}}" id="myInput" class="form-control rounded-1 d-inline-block" readonly="">
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary" type="button" onclick="CopyFunction()" id="instagram_chat_webhook"><i class="far fa-copy"></i>
                                {{__('Copy Link')}}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group col-sm-6">
                <label class="form-label ">{{ __('Instagram Client ID') }}</label> <br>
                <input class="form-control" placeholder="{{ __('Instagram Client ID') }}"
                    name="instagram_client_id" type="text"
                    value="{{ isset($settings['instagram_client_id']) ? $settings['instagram_client_id'] : '' }}"
                    id="instagram_client_id">
            </div>

            <div class="form-group col-sm-6">
                <label class="form-label ">{{ __('Instagram Access Token') }}</label> <br>
                <input class="form-control" placeholder="{{ __('Instagram Access Token') }}"
                    name="instagram_access_token" type="text"
                    value="{{ isset($settings['instagram_access_token']) ? $settings['instagram_access_token'] : '' }}"
                    id="instagram_access_token">
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <input class="btn btn-print-invoice  btn-primary" type="submit" value="{{ __('Save Changes') }}">
    </div>
    </form>
</div>
<script>
     function CopyFunction() {
        var copyText = document.getElementById("myInput");
        copyText.select();
        copyText.setSelectionRange(0, 99999)
        document.execCommand("copy");

        $('#instagram_chat_webhook').html('<i class="far fa-copy"></i> Copied!')
        setInterval(() => {
            $('#instagram_chat_webhook').html('<i class="far fa-copy"></i> Copy Link')
        }, 2000);

    }
</script>
