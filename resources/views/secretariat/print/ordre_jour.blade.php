@extends('secretariat.print._layout')

@section('body')
    <main class="sheet">
        @include('secretariat.print._head', [
            'title' => 'Ordre du jour',
            'sub' => $day !== '' ? \Illuminate\Support\Carbon::parse($day)->translatedFormat('l j F Y') : '',
        ])

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
