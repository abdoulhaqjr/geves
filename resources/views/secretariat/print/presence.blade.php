@extends('secretariat.print._layout')

@section('body')
    <main class="sheet">
        <div class="doc-head">
            <div class="app">{{ config('app.name') }} · Liste de présence</div>
            <h1>Liste de présence</h1>
            <div class="ev">{{ $event->title ?: $event->year }}</div>
            @if ($note !== '')<div class="note-line">{{ $note }}</div>@endif
        </div>

        @if ($participants->isEmpty())
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
                </tbody>
            </table>
        @endif
    </main>
@endsection
