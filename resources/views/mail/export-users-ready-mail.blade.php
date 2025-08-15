@component('mail::message')
# Your Export is Ready 📦

Your file **{{ $fileName }}** has been generated successfully.

@if($downloadUrl)
@component('mail::button', ['url' => $downloadUrl])
Download Now
@endcomponent
This link will expire in 48 hours for security reasons.
@else
The file has been attached to this email.
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
