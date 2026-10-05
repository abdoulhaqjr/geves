@extends('secretariat.print._layout')

@push('style')
    .chevalets { max-width: 820px; margin: 18px auto; }
    .tent { background: #fff; border: 1.5px solid var(--line); border-radius: 6px; margin: 0 auto 18px;
        page-break-inside: avoid; page-break-after: always; overflow: hidden; }
    .tent:last-child { page-break-after: auto; }
    .tent .face { height: 130px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 14px; }
    .tent .face .n { font-size: 34px; font-weight: 800; color: var(--blue); line-height: 1.1; }
    .tent .face .r { font-size: 16px; color: var(--muted); margin-top: 6px; text-transform: uppercase; letter-spacing: .6px; }
    .tent .face.flip { transform: rotate(180deg); }
    .tent .fold { border-top: 2px dashed var(--line); position: relative; }
    .tent .fold::after { content: "pli"; position: absolute; right: 8px; top: -8px; font-size: 9px; color: var(--muted); background: #fff; padding: 0 4px; }
    @media print { .chevalets { max-width: none; margin: 0; } .tent { border-color: #94a3b8; } }
@endpush

@section('body')
    <main class="chevalets">
        @forelse ($people as $p)
            <div class="tent">
                <div class="face flip"><div class="n">{{ $p['name'] }}</div><div class="r">{{ $p['role'] }}</div></div>
                <div class="fold"></div>
                <div class="face"><div class="n">{{ $p['name'] }}</div><div class="r">{{ $p['role'] }}</div></div>
            </div>
        @empty
            <p class="empty">Aucun modérateur ni rapporteur renseigné sur les séances.</p>
        @endforelse
    </main>
@endsection
