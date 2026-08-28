<x-layouts.admin title="Enregistrer un encaissement" subtitle="Saisie manuelle d’un paiement">
    <a href="{{ route('admin.payments.index') }}"
       style="display:inline-flex;align-items:center;gap:6px;font-size:.9rem;color:var(--ukc-muted);margin-bottom:14px;">
        <x-ukc.icon name="chevron" :size="16" style="transform:rotate(180deg);" /> Retour aux paiements
    </a>

    <form method="POST" action="{{ route('admin.payments.store') }}" class="ukc-card" style="max-width:720px;"
          x-data="{
              participants: @js($participants),
              editionId: '{{ old('edition_id', $defaultEdition) }}',
              participantId: '{{ old('participant_id', '') }}',
              amount: '{{ old('amount', '') }}',
              get filtered() { return this.participants.filter(p => String(p.edition_id) === String(this.editionId)); },
              onParticipant() {
                  const p = this.participants.find(x => String(x.id) === String(this.participantId));
                  if (p && p.amount != null) this.amount = String(p.amount);
              },
          }">
        @csrf
        <h2><x-ukc.icon name="card" :size="20" /> Nouvel encaissement</h2>

        <div class="ukc-form-grid">
            <div class="ukc-field full">
                <label for="edition_id">Édition <span class="req">*</span></label>
                <select id="edition_id" name="edition_id" x-model="editionId" @change="participantId=''">
                    <option value="">— Choisir —</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
                @error('edition_id') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="participant_id">Participant existant</label>
                <select id="participant_id" name="participant_id" x-model="participantId" @change="onParticipant()">
                    <option value="">— Nouveau participant —</option>
                    <template x-for="p in filtered" :key="p.id">
                        <option :value="p.id" x-text="p.name + ' (' + p.email + ')'"></option>
                    </template>
                </select>
                <span class="hint">Sélectionner pré-remplit le montant selon le profil.</span>
            </div>

            <template x-if="participantId === ''">
                <div style="display:contents;">
                    <div class="ukc-field">
                        <label for="first_name">Prénom</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}">
                        @error('first_name') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="last_name">Nom</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}">
                        @error('last_name') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field full">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}">
                        @error('email') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </template>

            <div class="ukc-field">
                <label for="amount">Montant (FCFA) <span class="req">*</span></label>
                <input type="number" id="amount" name="amount" x-model="amount" min="0" step="500">
                @error('amount') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="ukc-field">
                <label for="method">Moyen <span class="req">*</span></label>
                <select id="method" name="method">
                    @foreach ($methods as $key => $label)
                        <option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('method') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label for="note">Note</label>
                <textarea id="note" name="note" rows="2">{{ old('note') }}</textarea>
                @error('note') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="ukc-field full">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                    <input type="checkbox" name="markPaidNow" value="1" @checked(old('markPaidNow', true)) style="width:auto;padding:0;">
                    Marquer comme réglé immédiatement (génère le reçu + e-mail)
                </label>
            </div>
        </div>

        <div class="ukc-form-actions">
            <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer</button>
            <a href="{{ route('admin.payments.index') }}" class="ukc-btn ukc-btn--ghost">Annuler</a>
        </div>
    </form>
</x-layouts.admin>
