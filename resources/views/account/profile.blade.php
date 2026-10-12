<x-layouts.participant :title="__('Mon compte')" :subtitle="__('Profil, préférences, notifications & sécurité')">
    @include('partials.account-nav')

    <div class="ukc-card">
        <h3>{{ __('Mon profil') }}</h3>

        <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Photo de profil (fidèle au mockup) --}}
            <div class="ukc-avatar-upload" x-data="{
                    removed: false,
                    hasPhoto: {{ $account->photo_path ? 'true' : 'false' }},
                    preview: '{{ $account->photo_path ? \Illuminate\Support\Facades\Storage::url($account->photo_path) : '' }}',
                    pick(e) { const f = e.target.files[0]; if (!f) return; this.preview = URL.createObjectURL(f); this.hasPhoto = true; this.removed = false; },
                    clear() { this.preview = ''; this.hasPhoto = false; this.removed = true; this.$refs.photo.value = ''; }
                 }">
                <div class="ukc-avatar-preview" aria-label="Aperçu photo de profil">
                    <img x-show="preview" :src="preview" alt="Photo de profil">
                    <span x-show="!preview">{{ $account->initials }}</span>
                </div>
                <div class="ukc-avatar-actions">
                    <div style="font-size:14px;font-weight:700;color:var(--ukc-text);">{{ __('Photo de profil') }}</div>
                    <p class="hint">{{ __('JPG, PNG ou WebP - max. 2 Mo - format carré recommandé') }}</p>
                    <div class="row">
                        <input type="file" id="photo" name="photo" x-ref="photo" accept="image/jpeg,image/png,image/webp" @change="pick($event)" aria-label="{{ __('Choisir une photo') }}">
                        <label for="photo" class="ukc-btn ukc-btn--sm" style="cursor:pointer;">
                            <x-ukc.icon name="upload" :size="16" /> {{ __('Ajouter une photo') }}
                        </label>
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="hasPhoto" @click="clear()">{{ __('Supprimer') }}</button>
                    </div>
                    <input type="hidden" name="remove_photo" :value="removed ? '1' : '0'">
                    @error('photo') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="ukc-form-grid">
                <div class="ukc-field">
                    <label for="civility">{{ __('Civilité') }} <span class="req">*</span></label>
                    <select id="civility" name="civility">
                        <option value="">-</option>
                        @foreach ($civilities as $c)
                            <option value="{{ $c }}" @selected(old('civility', $account->civility) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                    @error('civility') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="grade">{{ __('Type de participant') }} <span class="req">*</span></label>
                    @php($currentType = old('grade', $account->grade))
                    <select id="grade" name="grade">
                        @foreach ($participantTypes as $t)
                            <option value="{{ $t }}" @selected($currentType === $t)>{{ $t }}</option>
                        @endforeach
                        @if (filled($currentType) && ! in_array($currentType, $participantTypes, true))
                            <option value="{{ $currentType }}" selected>{{ $currentType }}</option>
                        @endif
                    </select>
                    @error('grade') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="last_name">{{ __('Nom') }} <span class="req">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $account->last_name) }}" required>
                    @error('last_name') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="first_name">{{ __('Prénom(s)') }} <span class="req">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $account->first_name) }}" required>
                    @error('first_name') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="email">{{ __('E-mail') }} <span class="req">*</span></label>
                    <input type="email" id="email" value="{{ $account->email }}" readonly aria-readonly="true" style="background:var(--ukc-bg);cursor:not-allowed;">
                    <span class="hint">{{ __('Adresse de connexion - modifiable depuis l’onglet Sécurité.') }}</span>
                </div>

                <div class="ukc-field">
                    <label for="phone">{{ __('Téléphone') }} <span class="req">*</span></label>
                    <x-ukc.phone name="phone" :value="old('phone', $account->phone)" required />
                    @error('phone') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full" style="position:relative;"
                     x-data="ukcAffiliation({{ $halEnabled ? 'true' : 'false' }})" @click.outside="open = false">
                    <label for="affiliation">{{ __('Affiliation / Institution') }} <span class="req">*</span> <span class="hint">{{ __('(requise pour soumettre)') }}</span></label>
                    <input type="text" id="affiliation" name="affiliation" autocomplete="off" x-ref="input"
                           value="{{ old('affiliation', $account->affiliation) }}" @input="onInput()"
                           placeholder="{{ __('Laboratoire / Département - Université de Kara') }}">
                    @if ($halEnabled)
                        <ul x-show="open" x-cloak
                            style="position:absolute;z-index:20;left:0;right:0;top:100%;margin:4px 0 0;padding:6px;list-style:none;
                                   background:#fff;border:1px solid var(--ukc-border);border-radius:12px;box-shadow:var(--ukc-shadow);max-height:260px;overflow:auto;">
                            <template x-for="(s, i) in suggestions" :key="i">
                                <li>
                                    <button type="button" @click="choose(s.label)"
                                            style="width:100%;text-align:left;padding:9px 12px;border:0;background:none;border-radius:8px;cursor:pointer;font-size:.9rem;"
                                            onmouseover="this.style.background='var(--ukc-bg)'" onmouseout="this.style.background='none'">
                                        <span style="font-weight:600;" x-text="s.label"></span>
                                        <span style="color:var(--ukc-muted);" x-show="s.acronym" x-text="' - ' + s.acronym"></span>
                                        <span style="color:var(--ukc-light);" x-show="s.country" x-text="' (' + (s.country || '').toUpperCase() + ')'"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                        <span class="hint">{{ __('Commencez à taper : suggestions depuis HAL (AuréHAL).') }}</span>
                    @endif
                    @error('affiliation') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="orcid">{{ __('Identifiant ORCID') }} <span class="hint">{{ __('(facultatif)') }}</span>
                        @if ($account->orcidVerified())
                            <span class="ukc-pill ukc-pill--ok" style="margin-left:6px;"><x-ukc.icon name="check-circle" :size="12" /> {{ __('Vérifié') }}</span>
                        @endif
                    </label>
                    <input type="text" id="orcid" name="orcid" value="{{ old('orcid', $account->orcid) }}"
                           placeholder="0000-0002-1825-0097" inputmode="text" autocomplete="off">
                    @error('orcid')
                        <span class="error">{{ $message }}</span>
                    @else
                        @if ($account->orcidUrl())
                            <span class="hint">{{ __('Vérifiable :') }}
                                <a href="{{ $account->orcidUrl() }}" target="_blank" rel="noopener" style="color:var(--ukc-blue);font-weight:600;">{{ $account->orcid }}</a>
                            </span>
                        @else
                            <span class="hint">{{ __('Votre identifiant chercheur (orcid.org) - apparaîtra sur vos communications et attestations.') }}</span>
                        @endif
                    @enderror

                    @if ($orcidConnect && ! $account->orcidVerified())
                        <a href="{{ route('account.orcid.connect') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm" style="margin-top:8px;align-self:flex-start;">
                            <x-ukc.icon name="check-circle" :size="15" /> {{ __('Connecter avec ORCID (prouver l’appartenance)') }}
                        </a>
                    @endif
                </div>

                <div class="ukc-field">
                    <label for="country">{{ __('Pays') }}</label>
                    <input type="text" id="country" name="country" value="{{ old('country', $account->country) }}" placeholder="Togo">
                    @error('country') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field">
                    <label for="city">{{ __('Ville') }}</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $account->city) }}" placeholder="Kara">
                    @error('city') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-field full">
                    <label for="bio">{{ __('Biographie courte') }}</label>
                    <textarea id="bio" name="bio" rows="4" placeholder="{{ __('Quelques lignes sur votre parcours (optionnel).') }}">{{ old('bio', $account->bio) }}</textarea>
                    @error('bio') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="ukc-form-actions">
                <button type="submit" class="ukc-btn ukc-btn--blue">{{ __('Enregistrer') }}</button>
                <a href="{{ route('account.dashboard') }}" class="ukc-btn ukc-btn--ghost">{{ __('Annuler') }}</a>
            </div>
        </form>
    </div>
</x-layouts.participant>
