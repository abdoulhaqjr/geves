<x-mail::message>
@foreach (preg_split('/\r\n|\r|\n/', trim($bodyText)) as $line)
@if (trim($line) !== '')
{{ $line }}

@endif
@endforeach
Le comité d’organisation
</x-mail::message>
