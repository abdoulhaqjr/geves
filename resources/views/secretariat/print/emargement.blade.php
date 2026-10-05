@extends('secretariat.print._layout')

@section('body')
    <main class="sheet">
        <div class="doc-head">
            <div class="app">{{ config('app.name') }} · Feuille d'émargement</div>
            <h1>Feuille d'émargement</h1>
            <div class="ev">{{ $event->title ?: $event->year }}</div>
            <div class="note-line">{{ $note !== '' ? $note : 'Séance : ______________________     Date : ___________' }}</div>
        </div>

        <table class="grid">
            <thead>
                <tr><th class="num">#</th><th>Nom et prénom</th><th>Institution</th><th class="sign">Signature</th></tr>
            </thead>
            <tbody>
                @foreach ($participants as $i => $p)
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        <td>{{ trim($p->last_name.' '.$p->first_name) ?: $p->email }}</td>
                        <td>{{ $p->institution }}</td>
                        <td class="sign"></td>
                    </tr>
                @endforeach
                @for ($r = 0; $r < $extraRows; $r++)
                    <tr><td class="num">{{ $participants->count() + $r + 1 }}</td><td></td><td></td><td class="sign"></td></tr>
                @endfor
            </tbody>
        </table>
    </main>
@endsection
