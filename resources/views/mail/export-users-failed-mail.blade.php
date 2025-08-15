@component('mail::message')
# User Export Failed

Dear {{ $fullName }},

We attempted to generate your requested export file **{{ $fileName }}**, but unfortunately, the process did not complete successfully.

@if(!empty($errorMessage))
**Error details:**
{{ $errorMessage }}
@endif

Kindly try again.

Thanks,
{{ config('app.name') }}
@endcomponent
