<x-layouts.participant title="Mon profil" subtitle="Vos informations, préférences & sécurité">
    @include('partials.account-nav')

    @if (session('ukc_status'))
        <div class="ukc-alert ukc-alert--ok">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>{{ session('ukc_status') }}</div>
        </div>
    @endif

    <div class="ukc-card">
        <h2><x-ukc.icon name="user" :size="20" /> Mon profil</h2>

        <form method="POST" action="{{ route('account.profile.update') }}" class="ukc-form-grid">
            @csrf
            @method('PUT')
            <div class="ukc-field">
                <label for="civility">Civilité</label>
                <select id="civility" name="civility">
                    <option value="">—</option>
                    @foreach ($civilities as $c)
                        <option value="{{ $c }}" @selected(old('civility', $account->civility) === $c)>{{ $c }}</option>
                    @endforeach
                </select>
                @error('civility') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="grade">Grade / titre</label>
                <input type="text" id="grade" name="grade" value="{{ old('grade', $account->grade) }}" placeholder="Doctorant, Maître-assistant…">
                @error('grade') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="first_name">Prénom <span class="req">*</span></label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $account->first_name) }}">
                @error('first_name') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="last_name">Nom <span class="req">*</span></label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $account->last_name) }}">
                @error('last_name') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="phone">Téléphone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $account->phone) }}" placeholder="+228…">
                @error('phone') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field">
                <label for="country">Pays</label>
                <input type="text" id="country" name="country" value="{{ old('country', $account->country) }}" placeholder="Togo">
                @error('country') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full" style="position:relative;"
                 x-data="ukcAffiliation({{ $halEnabled ? 'true' : 'false' }})" @click.outside="open = false">
                <label for="affiliation">Affiliation <span class="req">*</span> <span class="hint">(requise pour soumettre)</span></label>
                <input type="text" id="affiliation" name="affiliation" autocomplete="off" x-ref="input"
                       value="{{ old('affiliation', $account->affiliation) }}" @input="onInput()"
                       placeholder="Laboratoire / Département — Université de Kara">
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
                                    <span style="color:var(--ukc-muted);" x-show="s.acronym" x-text="' · ' + s.acronym"></span>
                                    <span style="color:var(--ukc-light);" x-show="s.country" x-text="' (' + (s.country || '').toUpperCase() + ')'"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <span class="hint">Commencez à taper : suggestions depuis HAL (AuréHAL).</span>
                @endif
                @error('affiliation') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio" rows="4" placeholder="Quelques lignes sur vous (optionnel).">{{ old('bio', $account->bio) }}</textarea>
                @error('bio') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-form-actions full">
                <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer</button>
            </div>
        </form>
    </div>
</x-layouts.participant>
