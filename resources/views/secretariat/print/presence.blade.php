@extends('secretariat.print._layout')

@section('body')
    <main class="sheet">
        @include('secretariat.print._head', ['title' => 'Liste de présence', 'sub' => $note])

        @if ($participants->isEmpty() && $extraRows === 0)
            <p class="empty">Aucun participant inscrit pour cet événement.</p>
        @else
            <table class="grid">
                <thead>
                    <tr><th class="num">#</th><th>Nom et prénom</th><th>Institution</th><th>Catégorie</th><th class="sign">Émargement</th></tr>
                </thead>
                <tbody>
                    @foreach ($participants as $i => $p)
                        <tr>
                            <td class="num">{{ $i + 1 }}</td>
                            <td>{{ trim($p->last_name.' '.$p->first_name) ?: $p->email }}</td>
                            <td>{{ $p->institution }}</td>
                            <td>{{ $p->title }}</td>
                            <td class="sign"></td>
                        </tr>
                    @endforeach
                    @for ($r = 0; $r < $extraRows; $r++)
                        <tr><td class="num">{{ $participants->count() + $r + 1 }}</td><td></td><td></td><td></td><td class="sign"></td></tr>
                    @endfor
                </tbody>
            </table>
        @endif
    </main>
@endsection
