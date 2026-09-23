@php
    use Illuminate\Support\Str;
    $sessionSlots = $creneaux->where('session_id', $session->id)->sortBy('starts_at')->values();
    $sDay = optional($session->session_day ?? $session->starts_at);
    $wrap = ! ($inAtelier ?? false);
    $badge = $roomBadge ?? $session->room;
    // Données pour l'édition de la session (modale).
    $sessionJs = [
        'id' => $session->id,
        'type' => $session->atelier_id ? 'atelier' : 'plenary',
        'atelier_id' => (string) $session->atelier_id,
        'label' => $session->label,
        'day' => $sDay?->format('Y-m-d') ?? '',
        'start' => $session->starts_at?->format('H:i') ?? '',
        'end' => $session->ends_at?->format('H:i') ?? '',
        'room' => $session->room ?? '',
        'moderator' => $session->moderator_name ?? '',
        'rapporteur' => $session->rapporteur_name ?? '',
    ];
@endphp

@if ($wrap)
    <div class="atelier-block">
        <div class="atelier-head">
            <div>
                <h3>{{ $atelierName ?? 'Plénière' }}</h3>
                <div class="meta">{{ $sDay?->translatedFormat('d/m/Y') }}</div>
            </div>
            <div class="room-badge {{ $badge ? '' : 'pending' }}">{{ $badge ?: 'Salle non assignée' }}</div>
        </div>
@endif

<div class="session-block">
    <div class="session-head">
        <div>
            <div class="s-title">{{ $session->label }}</div>
            <div class="s-roles">
                Modérateur : <strong>{{ $session->moderator_name ?: 'À désigner' }}</strong>
                · Rapporteur : <strong>{{ $session->rapporteur_name ?: 'À désigner' }}</strong>
            </div>
        </div>
        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click='openSession(@json($sessionJs))'>Éditer session</button>
    </div>

    @forelse ($sessionSlots as $slot)
        @php
            $dur = $slotDuration($slot->submission);
            $end = $slot->starts_at ? $slot->starts_at->copy()->addMinutes($dur) : null;
            $slotJs = [
                'id' => $slot->id,
                'session_id' => (string) $slot->session_id,
                'day' => $slot->starts_at?->format('Y-m-d') ?? '',
                'start' => $slot->starts_at?->format('H:i') ?? '',
                'room' => $slot->room ?? '',
                'submission_id' => (string) $slot->submission_id,
                'title' => $slot->session_label ?? '',
            ];
        @endphp
        <div class="slot-row {{ $slot->submission ? '' : 'empty' }}">
            <div class="slot-time">
                {{ $slot->starts_at?->format('H:i') ?: '-' }}@if ($end) – {{ $end->format('H:i') }}@endif
                <span class="dur">{{ $dur }} min</span>
            </div>
            <div class="slot-body">
                @if ($slot->submission)
                    <div class="title">{{ Str::limit($slot->submission->title, 70) }}</div>
                    <div class="meta">{{ $slot->submission->participant?->full_name ?: 'Auteur' }} · {{ $slot->submission->ref_code }}</div>
                    <div class="tags">
                        <span class="ukc-pill ukc-pill--{{ $slot->submission->type === 'poster' ? 'poster' : 'oral' }}">{{ $slot->submission->type === 'poster' ? 'Poster' : 'Orale' }}</span>
                    </div>
                @else
                    <div class="title" style="color:var(--ukc-light);font-weight:600;">Créneau libre</div>
                    <div class="meta">Disponible pour affectation auto ou manuelle</div>
                @endif
            </div>
            <div class="slot-actions">
                @unless ($slot->submission)
                    <button type="button" class="ukc-btn ukc-btn--sm ukc-btn--ghost" @click='openSlot(@json($slotJs))'>Assigner</button>
                @endunless
                <button type="button" class="ukc-btn--icon" title="Modifier" @click='openSlot(@json($slotJs))'><x-ukc.icon name="pen" :size="15" /></button>
                @if ($slot->submission)
                    <form method="POST" action="{{ route('admin.program.slots.assign', $slot) }}" title="Retirer la communication">
                        @csrf
                        <button type="submit" class="ukc-btn--icon" title="Retirer"><x-ukc.icon name="close" :size="15" /></button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="slot-row empty"><div class="slot-time">-</div><div class="slot-body"><div class="meta">Aucun créneau dans cette session.</div></div><div></div></div>
    @endforelse
</div>

@if ($wrap)
    </div>
@endif
