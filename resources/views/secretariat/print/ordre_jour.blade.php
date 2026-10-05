@extends('secretariat.print._layout')

@section('body')
    <main class="sheet">
        <div class="doc-head">
            <div class="app">{{ config('app.name') }} · Ordre du jour</div>
            <h1>Ordre du jour</h1>
            <div class="ev">{{ $event->title ?: $event->year }}</div>
            @if ($day !== '')<div class="meta">{{ \Illuminate\Support\Carbon::parse($day)->translatedFormat('l j F Y') }}</div>@endif
        </div>

        @if ($sessions->isEmpty())
            <p class="empty">Aucune séance programmée pour ce jour.</p>
        @else
            <table class="grid">
                <thead>
                    <tr><th style="width:120px;">Horaire</th><th>Séance</th><th>Salle</th><th>Modérateur</th><th>Rapporteur</th></tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $s)
                        <tr>
                            <td>{{ $s->starts_at?->format('H:i') }}@if ($s->ends_at) - {{ $s->ends_at->format('H:i') }}@endif</td>
                            <td>{{ $s->label }}</td>
                            <td>{{ $s->room ?: '-' }}</td>
                            <td>{{ $s->moderator_name ?: '-' }}</td>
                            <td>{{ $s->rapporteur_name ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </main>
@endsection
