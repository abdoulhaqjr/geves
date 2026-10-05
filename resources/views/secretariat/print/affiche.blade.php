@extends('secretariat.print._layout')

@push('style')
    .poster { background: #fff; min-height: calc(100vh - 44px); display: flex; flex-direction: column;
        align-items: center; justify-content: center; text-align: center; padding: 40px; page-break-after: always; }
    .poster:last-child { page-break-after: auto; }
    .poster .poster-ev { font-size: 18px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
    .poster .poster-arrow { font-size: 60px; color: var(--blue); margin: 10px 0; }
    .poster .poster-room { font-size: 72px; font-weight: 900; color: var(--blue); line-height: 1.05; margin: 8px 0 24px; }
    .poster .poster-list { list-style: none; padding: 0; margin: 0; font-size: 26px; }
    .poster .poster-list li { margin: 10px 0; }
    .poster .poster-list .t { color: var(--muted); font-size: 20px; margin-left: 10px; }
    @media print { @page { size: A4 landscape; margin: 14mm; } .poster { min-height: 92vh; } }
@endpush

@section('body')
    @forelse ($byRoom as $room => $roomSessions)
        <section class="poster">
            <div class="poster-ev">{{ $event->title ?: $event->year }}</div>
            <div class="poster-arrow">&#8595;</div>
            <div class="poster-room">{{ $room }}</div>
            <ul class="poster-list">
                @foreach ($roomSessions as $s)
                    <li>{{ $s->label }}@if ($s->starts_at)<span class="t">{{ $s->starts_at->format('H:i') }}</span>@endif</li>
                @endforeach
            </ul>
        </section>
    @empty
        <main class="sheet"><p class="empty">Aucune salle renseignée pour ces séances.</p></main>
    @endforelse
@endsection
