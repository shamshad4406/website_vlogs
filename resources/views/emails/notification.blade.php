<x-mail::message>
# {{ $heading }}

@if($name)
Hi {{ $name }},
@endif

{{ $body }}

@if($link)
<x-mail::button :url="$link">
Open on {{ config('app.name') }}
</x-mail::button>
@endif

Thanks,<br>
The {{ config('app.name') }} team
</x-mail::message>
