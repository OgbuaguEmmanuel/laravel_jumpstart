@component('mail::message')
# User Import Report

@if($failures->isEmpty())
All users were imported successfully. 🎉
@else
Some rows could not be imported. Please review the details below:

@component('mail::table')
| Row | Attribute | Error(s) | Values |
| --- | --------- | -------- | ------ |
@foreach ($failures as $failure)
| {{ $failure->row() }}
| {{ $failure->attribute() }}
| {{ implode(', ', $failure->errors()) }}
| {{ json_encode($failure->values()) }} |
@endforeach
@endcomponent

You can re-upload a corrected file at any time.
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
