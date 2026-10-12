<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }} - {{ __('Université de Kara') }}
{{ __('Cet e-mail vous a été envoyé par :app, plateforme de gestion des événements scientifiques.', ['app' => config('app.name')]) }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
