<x-layouts.participant :title="__('Mon témoignage')" :subtitle="__('Partagez votre expérience - les meilleurs témoignages figurent sur la page d’accueil')">
    <div class="account-testimonials" style="max-width:720px;">

        {{-- Mes témoignages déjà déposés --}}
        @if ($mine->isNotEmpty())
            <div class="ukc-card" style="margin-bottom:18px;">
                <h2>{{ __('Mes témoignages') }}</h2>
                @foreach ($mine as $t)
                    <div style="border:1px solid var(--ukc-border);border-radius:10px;padding:12px 14px;margin-top:10px;">
                        <blockquote style="margin:0 0 8px;font-style:italic;">« {{ $t->quote }} »</blockquote>
                        @php $pill = ['pending' => 'warn', 'approved' => 'ok', 'rejected' => 'err'][$t->status] ?? 'muted'; @endphp
                        <span class="ukc-pill ukc-pill--{{ $pill }}">{{ $t->statusLabel() }}</span>
                        @if ($t->featured)
                            <span class="ukc-pill ukc-pill--info">{{ __('À la une sur l’accueil') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="ukc-card">
            <h2>{{ __('Laisser un témoignage') }}</h2>
            <p style="font-size:13.5px;color:var(--ukc-muted);margin-bottom:14px;">
                {{ __('Votre témoignage sera relu par l’équipe avant publication. Les plus marquants sont mis en avant sur la page d’accueil de la plateforme.') }}
            </p>

            @if ($pendingExists)
                <div class="ukc-pill ukc-pill--warn" style="display:inline-flex;align-items:center;gap:6px;">
                    <x-ukc.icon name="clock" :size="14" /> {{ __('Vous avez déjà un témoignage en attente de modération.') }}
                </div>
            @else
                <form method="POST" action="{{ route('account.testimonials.store') }}">
                    @csrf
                    <div class="ukc-field">
                        <label for="quote">{{ __('Votre témoignage') }} <span class="req">*</span></label>
                        <textarea id="quote" name="quote" rows="4" minlength="20" maxlength="2000" required
                                  placeholder="{{ __('Ce que la plateforme ou l’événement vous a apporté…') }}">{{ old('quote') }}</textarea>
                        @error('quote') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="author_role">{{ __('Qualité / rôle') }}</label>
                        <input type="text" id="author_role" name="author_role" maxlength="160"
                               value="{{ old('author_role', $account->grade) }}" placeholder="{{ __('Ex. Doctorante · Sciences sociales') }}">
                    </div>
                    <div class="ukc-field">
                        <label for="author_affiliation">{{ __('Affiliation') }}</label>
                        <input type="text" id="author_affiliation" name="author_affiliation" maxlength="200"
                               value="{{ old('author_affiliation', $account->affiliation) }}">
                    </div>
                    @if ($editions->isNotEmpty())
                        <div class="ukc-field">
                            <label for="edition_id">{{ __('À propos de quel événement ? (optionnel)') }}</label>
                            <select id="edition_id" name="edition_id">
                                <option value="">- {{ __('Non précisé') }} -</option>
                                @foreach ($editions as $ed)
                                    <option value="{{ $ed->id }}" @selected((string) old('edition_id') === (string) $ed->id)>{{ $ed->title ?: $ed->year }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="ukc-field">
                        <label for="rating">{{ __('Note (optionnelle)') }}</label>
                        <select id="rating" name="rating">
                            <option value="">- {{ __('Sans note') }} -</option>
                            @for ($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" @selected((string) old('rating') === (string) $i)>{{ $i }}/5</option>
                            @endfor
                        </select>
                    </div>
                    <label class="ukc-check" style="display:flex;align-items:flex-start;gap:8px;margin:6px 0 14px;cursor:pointer;">
                        <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                        <span>{{ __('J’autorise l’Université de Kara à publier ce témoignage (avec mon nom et ma qualité) sur la plateforme.') }}</span>
                    </label>
                    @error('consent') <span class="error">{{ $message }}</span> @enderror
                    <div>
                        <button type="submit" class="ukc-btn ukc-btn--blue">
                            <x-ukc.icon name="sparkles" :size="16" /> {{ __('Envoyer mon témoignage') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.participant>
