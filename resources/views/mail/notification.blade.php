<x-mail::message>
# {{ $title }}

@if($body)
{{ $body }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
