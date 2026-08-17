<div style="max-width:820px;">
    @if (session('ukc_status'))
        <div class="ukc-alert ukc-alert--ok">
            <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
            <div>{{ session('ukc_status') }}</div>
        </div>
    @endif

    <div class="ukc-card" style="margin-bottom:18px;">
        <h2>
            <span><x-ukc.icon name="sparkles" :size="20" /> Agents IA</span>
            <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" wire:click="addAgent">
                <x-ukc.icon name="plus" :size="16" /> Ajouter un agent
            </button>
        </h2>
        <p style="font-size:.88rem;color:var(--ukc-muted);margin:0;">
            Personnalité et garde-fous de chaque agent. Les agents d’administration ne peuvent que
            <strong>proposer</strong> des actions : la décision finale reste à l’administrateur.
        </p>

        {{-- Onglets agents --}}
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
            @foreach ($agents as $i => $agent)
                <button type="button" wire:key="tab-{{ $i }}" wire:click="$set('active', {{ $i }})"
                        @class(['ukc-btn', 'ukc-btn--sm', 'ukc-btn--blue' => $active === $i, 'ukc-btn--ghost' => $active !== $i])>
                    {{ $agent['name'] ?: 'Agent' }}
                    <span style="opacity:.7;font-weight:600;">· {{ $agent['audience'] === 'admin' ? 'admin' : 'participants' }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @if (isset($agents[$active]))
        <form wire:submit="save" wire:key="editor-{{ $active }}">
            <div class="ukc-card" style="margin-bottom:18px;">
                <div class="ukc-form-grid">
                    <div class="ukc-field">
                        <label for="a-name">Nom de l’agent</label>
                        <input type="text" id="a-name" wire:model="agents.{{ $active }}.name">
                        @error('agents.'.$active.'.name') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="a-audience">Public cible</label>
                        <select id="a-audience" wire:model="agents.{{ $active }}.audience">
                            <option value="participants">Participants (chat public)</option>
                            <option value="admin">Administration (copilote)</option>
                        </select>
                    </div>
                    <div class="ukc-field full">
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" wire:model="agents.{{ $active }}.enabled"> Agent actif
                        </label>
                    </div>
                </div>

                <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;">Persona — instruction système</h3>
                <div class="ukc-field" style="margin:0;">
                    <textarea rows="6" wire:model="agents.{{ $active }}.persona"
                              placeholder="Qui est l’agent, son ton, son périmètre…"></textarea>
                    @error('agents.'.$active.'.persona') <span class="error">{{ $message }}</span> @enderror
                </div>

                <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;color:#166534;">Instructions POSITIVES — à toujours faire</h3>
                <div class="ukc-field" style="margin:0;">
                    <textarea rows="5" wire:model="agents.{{ $active }}.positive"
                              placeholder="- Orienter vers l’espace participant…"></textarea>
                    @error('agents.'.$active.'.positive') <span class="error">{{ $message }}</span> @enderror
                </div>

                <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;color:#991b1b;">Instructions NÉGATIVES — à ne jamais faire</h3>
                <div class="ukc-field" style="margin:0;">
                    <textarea rows="5" wire:model="agents.{{ $active }}.negative"
                              placeholder="- Ne jamais inventer de tarifs ni de décisions…"></textarea>
                    @error('agents.'.$active.'.negative') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="ukc-form-actions" style="justify-content:space-between;">
                    <button type="submit" class="ukc-btn ukc-btn--blue" wire:loading.attr="disabled" wire:target="save">
                        <x-ukc.icon name="check" :size="18" /> Enregistrer les agents
                    </button>
                    @if (count($agents) > 1)
                        <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm"
                                wire:click="removeAgent({{ $active }})"
                                wire:confirm="Supprimer cet agent ?">
                            <x-ukc.icon name="trash" :size="16" /> Supprimer cet agent
                        </button>
                    @endif
                </div>
            </div>
        </form>
    @endif
</div>
