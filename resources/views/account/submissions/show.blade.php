<x-layouts.participant title="Ma communication" subtitle="Suivi, retours & resoumission">
    @php($s = $submission)

    <a href="{{ route('account.submissions.index') }}"
       style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--ukc-muted);margin-bottom:14px;">
        <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Mes communications
    </a>

    <div class="ukc-card" style="margin-bottom:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
            <span style="font-family:ui-monospace,monospace;font-weight:700;color:var(--ukc-muted);">{{ $s->ref_code }}</span>
            <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span>
        </div>
        <h2 style="margin-top:10px;">{{ $s->title }}</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
            @if ($s->track)<span class="ukc-pill ukc-pill--info">{{ $s->track }}</span>@endif
            <span class="ukc-pill ukc-pill--muted">{{ $s->type === 'poster' ? 'Poster' : 'Oral' }}</span>
            <span class="ukc-pill ukc-pill--muted">Version {{ $s->version }}</span>
        </div>

        {{-- Progression du statut (fidèle au mockup soumission-detail) --}}
        @php
            $decided = in_array($s->status, ['accepted', 'rejected'], true);
            $evaluated = in_array($s->status, ['under_review', 'revision', 'accepted', 'rejected'], true);
        @endphp
        <div class="ukc-steps" style="margin-top:16px;">
            <div class="ukc-step done">Déposée</div>
            <div @class(['ukc-step', 'done' => $evaluated && $s->status !== 'under_review', 'active' => $s->status === 'under_review'])>En évaluation</div>
            <div @class(['ukc-step', 'done' => $decided, 'active' => $s->status === 'revision'])>{{ $s->status === 'rejected' ? 'Non retenue' : ($s->status === 'revision' ? 'Correction' : 'Décision') }}</div>
        </div>
    </div>

    {{-- Retour des relecteurs --}}
    @if (count($feedback))
        <div class="ukc-card" style="margin-bottom:18px;">
            <h3><x-ukc.icon name="mic" :size="20" /> Retour des relecteurs</h3>
            @foreach ($feedback as $fb)
                <div style="padding:12px 0;border-bottom:1px solid var(--ukc-border);">
                    <div style="font-weight:700;font-size:.9rem;color:var(--ukc-blue);">{{ $fb['label'] }}</div>
                    <p style="color:var(--ukc-text);line-height:1.6;white-space:pre-line;margin-top:4px;">{{ $fb['comments'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Créneau de passage (communication acceptée) --}}
    @if ($s->status === 'accepted')
        <div class="ukc-card" style="margin-bottom:18px;">
            <h3><x-ukc.icon name="calendar" :size="20" /> Créneau de passage</h3>
            @if ($slot)
                <div class="ukc-alert ukc-alert--ok" style="margin:8px 0 0;">
                    <span class="ico"><x-ukc.icon name="check" :size="18" /></span>
                    <div>
                        <strong>{{ $slot->session_label ?: 'Présentation' }}</strong>
                        @if ($slot->starts_at) — {{ $slot->starts_at->translatedFormat('d F, H:i') }} @endif
                        @if ($slot->room) · Salle {{ $slot->room }} @endif
                        @if ($slot->chosen_by_participant) <span class="ukc-pill ukc-pill--info" style="margin-left:6px;">Choisi</span> @endif
                    </div>
                </div>
            @endif

            @if ($availableSlots->count())
                <p style="font-size:.88rem;color:var(--ukc-muted);margin:14px 0 8px;">
                    {{ $slot ? 'Vous pouvez changer de créneau :' : 'Choisissez votre créneau de passage :' }}
                </p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach ($availableSlots as $av)
                        <form method="POST" action="{{ route('account.submissions.choose-slot', $s) }}">
                            @csrf
                            <input type="hidden" name="slot_id" value="{{ $av->id }}">
                            <button type="submit" class="ukc-btn ukc-btn--outline" style="justify-content:space-between;width:100%;">
                                <span>{{ $av->session_label ?: 'Créneau' }}
                                    @if ($av->starts_at) — {{ $av->starts_at->translatedFormat('d F, H:i') }} @endif
                                    @if ($av->room) · {{ $av->room }} @endif
                                </span>
                                <x-ukc.icon name="chevron" :size="16" />
                            </button>
                        </form>
                    @endforeach
                </div>
            @elseif (! $slot)
                <p style="font-size:.88rem;color:var(--ukc-muted);">Aucun créneau disponible pour l’instant — l’organisation vous en attribuera un.</p>
            @endif
        </div>
    @endif

    {{-- Resoumission --}}
    @if ($s->canBeRevised())
        <div class="ukc-card" style="margin-bottom:18px;">
            <div class="ukc-alert ukc-alert--warn" style="margin-bottom:16px;">
                <span class="ico"><x-ukc.icon name="pen" :size="18" /></span>
                <div>Une <strong>correction</strong> vous est demandée. Modifiez votre communication ci-dessous puis renvoyez-la.</div>
            </div>

            <form method="POST" action="{{ route('account.submissions.resubmit', $s) }}" enctype="multipart/form-data" class="ukc-form-grid">
                @csrf
                <div class="ukc-field full">
                    <label for="title">Titre <span class="req">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title', $s->title) }}">
                    @error('title') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full" x-data="ukcTags(@js(old('keywords', $s->keywords)))">
                    <input type="hidden" name="keywords" :value="tags.join(', ')">
                    <label for="keywords-input">Mots-clés</label>
                    <div class="ukc-tags" @click="$refs.kwinput.focus()">
                        <template x-for="(tag, i) in tags" :key="tag">
                            <span class="ukc-tag"><span x-text="tag"></span><button type="button" @click="remove(i)" aria-label="Retirer">&times;</button></span>
                        </template>
                        <input type="text" id="keywords-input" x-ref="kwinput" x-model="draft"
                               @keydown.enter.prevent="add()" @keydown.comma.prevent="add()"
                               @keydown.backspace="backspace()" @blur="add()"
                               placeholder="Saisir un mot-clé puis Entrée…">
                    </div>
                    @error('keywords') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full">
                    <label>Résumé <span class="req">*</span></label>
                    @include('partials.abstract-editor', ['name' => 'abstract', 'initial' => old('abstract', $s->abstract), 'min' => 50])
                    @error('abstract') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full">
                    <label for="file">Remplacer le fichier <span class="hint">(optionnel)</span></label>
                    <input type="file" id="file" name="file" accept=".pdf,.doc,.docx">
                    @error('file') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-form-actions full">
                    <button type="submit" class="ukc-btn ukc-btn--blue">
                        <x-ukc.icon name="upload" :size="18" /> Renvoyer la version corrigée
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="ukc-card" style="margin-bottom:18px;">
            <h3>Résumé</h3>
            <div class="ukc-rte">{!! \App\Support\Html::render($s->abstract) !!}</div>
            @if ($s->file_url)
                <a href="{{ $s->file_url }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="margin-top:14px;">
                    <x-ukc.icon name="file" :size="16" /> Fichier joint
                </a>
            @endif
        </div>
    @endif

    {{-- Historique des versions --}}
    @if ($versions->count())
        <div class="ukc-card">
            <h3><x-ukc.icon name="clock" :size="20" /> Historique des versions</h3>
            <ul style="list-style:none;">
                @foreach ($versions as $v)
                    <li style="display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--ukc-border);font-size:.9rem;">
                        <span>Version {{ $v->version }} — {{ \Illuminate\Support\Str::limit($v->title, 60) }}</span>
                        <span style="color:var(--ukc-muted);">{{ $v->created_at?->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-layouts.participant>
