@extends('secretariat.print._layout')

@section('body')
    <main class="sheet">
        @include('secretariat.print._head', [
            'title' => "Feuille d'émargement",
            'sub' => $note !== '' ? $note : 'Séance : ______________________     Date : ___________',
        ])

        <table class="grid">
            <thead>
                <tr><th class="num">#</th><th>Participant</th><th>Institution</th><th class="sign">Signature</th></tr>
            </thead>
            <tbody>
                @foreach ($participants as $i => $p)
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        <td>{{ $p->full_name ?: $p->email }}</td>
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
