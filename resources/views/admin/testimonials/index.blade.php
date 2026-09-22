<x-layouts.admin title="Témoignages" subtitle="Recueillis auprès des participants — un sous-ensemble « à la une » s’affiche sur la page d’accueil">
    <div class="admin-page admin-testimonials">

        {{-- Barre d'action : proposition IA --}}
        <div class="ukc-card" style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:18px;">
            <div>
                <h2 style="margin:0;">Sélection « à la une »</h2>
                <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 0;">
                    L’IA note l’impact des témoignages approuvés et propose lesquels mettre en avant. Vous gardez la main :
                    activez/retirez la mise en avant et réordonnez à votre convenance.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.testimonials.propose') }}">
                @csrf
                <button type="submit" class="ukc-btn ukc-btn--blue" @disabled(! $aiAvailable)
                        title="{{ $aiAvailable ? '' : 'Aucun assistant IA configuré (Réglages → Intelligence artificielle).' }}">
                    <x-ukc.icon name="sparkles" :size="16" /> Proposer la sélection (IA)
                </button>
            </form>
        </div>

        {{-- Réordonnancement des témoignages à la une --}}
        @php $featured = $approved->where('featured', true)->values(); @endphp
        @if ($featured->isNotEmpty())
            <div class="ukc-card" style="margin-bottom:18px;"
                 x-data="{ items: {{ Illuminate\Support\Js::from($featured->map(fn ($t) => ['id' => $t->id, 'name' => $t->author_name])) }},
                           move(i, d){ const j=i+d; if(j<0||j>=this.items.length) return; [this.items[i],this.items[j]]=[this.items[j],this.items[i]]; } }">
                <h2>À la une sur l’accueil <span class="ukc-badge-count">{{ $featured->count() }}</span></h2>
                <p style="font-size:13px;color:var(--ukc-muted);margin-bottom:12px;">Ordre d’affichage sur la page d’accueil (du haut vers le bas).</p>
                <form method="POST" action="{{ route('admin.testimonials.reorder') }}">
                    @csrf
                    <ul style="list-style:none;padding:0;margin:0 0 12px;display:flex;flex-direction:column;gap:8px;">
                        <template x-for="(item, i) in items" :key="item.id">
                            <li style="display:flex;align-items:center;gap:10px;border:1px solid var(--ukc-border);border-radius:8px;padding:8px 12px;">
                                <input type="hidden" name="order[]" :value="item.id">
                                <span style="font-weight:700;color:var(--ukc-muted);min-width:22px;" x-text="i+1"></span>
                                <strong x-text="item.name" style="flex:1;"></strong>
                                <button type="button" class="ukc-btn--icon" @click="move(i,-1)" :disabled="i===0" aria-label="Monter">▲</button>
                                <button type="button" class="ukc-btn--icon" @click="move(i,1)" :disabled="i===items.length-1" aria-label="Descendre">▼</button>
                            </li>
                        </template>
                    </ul>
                    <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="16" /> Enregistrer l’ordre</button>
                </form>
            </div>
        @endif

        <div class="ukc-grid-2" style="align-items:start;gap:18px;">
            {{-- En attente de modération --}}
            <div class="ukc-card">
                <h2>En attente <span class="ukc-badge-count">{{ $pending->count() }}</span></h2>
                @forelse ($pending as $t)
                    <div style="border:1px solid var(--ukc-border);border-radius:10px;padding:12px 14px;margin-bottom:10px;">
                        <blockquote style="margin:0 0 8px;font-style:italic;">« {{ $t->quote }} »</blockquote>
                        <div style="font-size:13px;color:var(--ukc-muted);margin-bottom:10px;">
                            <strong>{{ $t->author_name }}</strong>
                            {{ collect([$t->author_role, $t->author_affiliation])->filter()->implode(' · ') }}
                            @if ($t->edition) · <span>{{ $t->edition->title }}</span> @endif
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <form method="POST" action="{{ route('admin.testimonials.approve', $t) }}">@csrf
                                <button class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> Approuver</button>
                            </form>
                            <form method="POST" action="{{ route('admin.testimonials.reject', $t) }}">@csrf
                                <button class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="close" :size="14" /> Refuser</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p style="font-size:13.5px;color:var(--ukc-light);">Aucun témoignage en attente.</p>
                @endforelse

                {{-- Saisie manuelle par l'admin --}}
                <details style="margin-top:14px;">
                    <summary style="cursor:pointer;font-weight:600;">Ajouter un témoignage manuellement</summary>
                    <form method="POST" action="{{ route('admin.testimonials.store') }}" style="margin-top:12px;">
                        @csrf
                        <div class="ukc-field"><label>Nom <span class="req">*</span></label>
                            <input type="text" name="author_name" value="{{ old('author_name') }}" maxlength="160" required></div>
                        <div class="ukc-field"><label>Qualité / rôle</label>
                            <input type="text" name="author_role" value="{{ old('author_role') }}" maxlength="160" placeholder="Ex. Doctorante · Sciences sociales"></div>
                        <div class="ukc-field"><label>Affiliation</label>
                            <input type="text" name="author_affiliation" value="{{ old('author_affiliation') }}" maxlength="200"></div>
                        <div class="ukc-field"><label>Témoignage <span class="req">*</span></label>
                            <textarea name="quote" rows="3" maxlength="2000" required>{{ old('quote') }}</textarea>
                            @error('quote') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <label class="ukc-check" style="display:flex;align-items:center;gap:8px;margin:4px 0 12px;">
                            <input type="checkbox" name="featured" value="1" @checked(old('featured'))> <span>Mettre à la une tout de suite</span></label>
                        <button class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="plus" :size="14" /> Ajouter</button>
                    </form>
                </details>
            </div>

            {{-- Approuvés --}}
            <div class="ukc-card">
                <h2>Approuvés <span class="ukc-badge-count">{{ $approved->count() }}</span></h2>
                @forelse ($approved as $t)
                    <div style="border:1px solid var(--ukc-border);border-radius:10px;padding:12px 14px;margin-bottom:10px;
                                {{ $t->featured ? 'border-color:var(--ukc-green);background:rgba(0,153,51,.04);' : '' }}">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <blockquote style="margin:0 0 8px;font-style:italic;flex:1;">« {{ $t->quote }} »</blockquote>
                            @if (! is_null($t->impact_score))
                                <span class="ukc-pill ukc-pill--info" title="{{ $t->impact_reason }}">Impact {{ $t->impact_score }}</span>
                            @endif
                        </div>
                        <div style="font-size:13px;color:var(--ukc-muted);margin-bottom:10px;">
                            <strong>{{ $t->author_name }}</strong>
                            {{ collect([$t->author_role, $t->author_affiliation])->filter()->implode(' · ') }}
                        </div>
                        @if ($t->impact_reason)
                            <p style="font-size:12.5px;color:var(--ukc-light);margin:0 0 10px;">IA : {{ $t->impact_reason }}</p>
                        @endif
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <form method="POST" action="{{ route('admin.testimonials.feature', $t) }}">@csrf
                                <button class="ukc-btn ukc-btn--sm {{ $t->featured ? 'ukc-btn--gold' : 'ukc-btn--outline' }}">
                                    <x-ukc.icon name="{{ $t->featured ? 'eye' : 'eye-off' }}" :size="14" />
                                    {{ $t->featured ? 'À la une' : 'Mettre à la une' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.testimonials.destroy', $t) }}"
                                  onsubmit="return confirm('Supprimer ce témoignage ?')">@csrf @method('DELETE')
                                <button class="ukc-btn--icon" title="Supprimer" aria-label="Supprimer"><x-ukc.icon name="trash" :size="16" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p style="font-size:13.5px;color:var(--ukc-light);">Aucun témoignage approuvé.</p>
                @endforelse
            </div>
        </div>

        {{-- Refusés (repli discret) --}}
        @if ($rejected->isNotEmpty())
            <details class="ukc-card" style="margin-top:18px;">
                <summary style="cursor:pointer;font-weight:600;">Refusés <span class="ukc-badge-count">{{ $rejected->count() }}</span></summary>
                <div style="margin-top:12px;display:flex;flex-direction:column;gap:8px;">
                    @foreach ($rejected as $t)
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;border:1px solid var(--ukc-border);border-radius:8px;padding:8px 12px;">
                            <span style="font-size:13px;color:var(--ukc-muted);flex:1;">« {{ Str::limit($t->quote, 120) }} » — <strong>{{ $t->author_name }}</strong></span>
                            <form method="POST" action="{{ route('admin.testimonials.approve', $t) }}">@csrf
                                <button class="ukc-btn ukc-btn--outline ukc-btn--sm">Réhabiliter</button>
                            </form>
                            <form method="POST" action="{{ route('admin.testimonials.destroy', $t) }}"
                                  onsubmit="return confirm('Supprimer définitivement ?')">@csrf @method('DELETE')
                                <button class="ukc-btn--icon" title="Supprimer" aria-label="Supprimer"><x-ukc.icon name="trash" :size="16" /></button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </div>
</x-layouts.admin>
