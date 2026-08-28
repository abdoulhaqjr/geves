<x-layouts.admin title="Agents IA" subtitle="Personas & garde-fous des agents IA">
    <div style="max-width:820px;" x-data="agentsEditor(@js($agents))">
        @if (session('ukc_status'))
            <div class="ukc-alert ukc-alert--ok">
                <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
                <div>{{ session('ukc_status') }}</div>
            </div>
        @endif

        <div class="ukc-card" style="margin-bottom:18px;">
            <h2>
                <span><x-ukc.icon name="sparkles" :size="20" /> Agents IA</span>
                <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="addAgent()">
                    <x-ukc.icon name="plus" :size="16" /> Ajouter un agent
                </button>
            </h2>
            <p style="font-size:.88rem;color:var(--ukc-muted);margin:0;">
                Personnalité et garde-fous de chaque agent. Les agents d’administration ne peuvent que
                <strong>proposer</strong> des actions : la décision finale reste à l’administrateur.
            </p>

            {{-- Onglets agents --}}
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
                <template x-for="(agent, i) in agents" :key="i">
                    <button type="button" @click="active = i"
                            class="ukc-btn ukc-btn--sm" :class="active === i ? 'ukc-btn--blue' : 'ukc-btn--ghost'">
                        <span x-text="agent.name || 'Agent'"></span>
                        <span style="opacity:.7;font-weight:600;" x-text="'· ' + (agent.audience === 'admin' ? 'admin' : 'participants')"></span>
                    </button>
                </template>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.agents.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <template x-for="(agent, i) in agents" :key="i">
                <div class="ukc-card" style="margin-bottom:18px;" x-show="active === i">
                    <input type="hidden" :name="'agents['+i+'][key]'" :value="agent.key">
                    <input type="hidden" :name="'agents['+i+'][avatar]'" :value="agent.avatar">

                    {{-- Avatar de l'agent --}}
                    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-bottom:18px;padding-bottom:18px;border-bottom:1px solid var(--ukc-border);"
                         x-data="{ removed: false, fileName: '' }">
                        <div style="width:72px;height:72px;border-radius:50%;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,var(--ukc-blue),var(--ukc-green));color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(20,40,90,.12);">
                            <template x-if="agent.avatar && !removed">
                                <img :src="'/storage/' + agent.avatar" alt="Avatar de l’agent" style="width:100%;height:100%;object-fit:cover;">
                            </template>
                            <template x-if="!agent.avatar || removed">
                                <span><x-ukc.icon name="sparkles" :size="30" /></span>
                            </template>
                        </div>
                        <div style="min-width:0;">
                            <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px;">Avatar de l’agent</label>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <label class="ukc-btn ukc-btn--outline ukc-btn--sm" style="cursor:pointer;">
                                    <x-ukc.icon name="upload" :size="16" />
                                    <span x-text="fileName || 'Choisir une image'"></span>
                                    <input type="file" :name="'avatars['+i+']'" @change="fileName = $event.target.files[0]?.name || ''; removed = false"
                                           accept="image/png,image/jpeg,image/webp,image/svg+xml" style="display:none;">
                                </label>
                                <template x-if="agent.avatar">
                                    <label class="ukc-btn ukc-btn--ghost ukc-btn--sm" style="cursor:pointer;">
                                        <input type="checkbox" :name="'remove_avatar['+i+']'" value="1" x-model="removed" style="display:none;">
                                        <span x-text="removed ? 'Sera retiré' : 'Retirer'"></span>
                                    </label>
                                </template>
                            </div>
                            @error('avatars.*') <span class="error">{{ $message }}</span> @enderror
                            <p style="font-size:12px;color:var(--ukc-light);margin-top:6px;">PNG, JPG, WEBP ou SVG · 2 Mo max. Affiché dans le chat de l’agent.</p>
                        </div>
                    </div>

                    <div class="ukc-form-grid">
                        <div class="ukc-field">
                            <label>Nom de l’agent</label>
                            <input type="text" :name="'agents['+i+'][name]'" x-model="agent.name">
                        </div>
                        <div class="ukc-field">
                            <label>Public cible</label>
                            <select :name="'agents['+i+'][audience]'" x-model="agent.audience">
                                <option value="participants">Participants (chat public)</option>
                                <option value="admin">Administration (copilote)</option>
                            </select>
                        </div>
                        <div class="ukc-field full">
                            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" :name="'agents['+i+'][enabled]'" value="1" x-model="agent.enabled"> Agent actif
                            </label>
                        </div>
                    </div>

                    <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;">Persona — instruction système</h3>
                    <div class="ukc-field" style="margin:0;">
                        <textarea rows="6" :name="'agents['+i+'][persona]'" x-model="agent.persona"
                                  placeholder="Qui est l’agent, son ton, son périmètre…"></textarea>
                    </div>

                    <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;color:#166534;">Instructions POSITIVES — à toujours faire</h3>
                    <div class="ukc-field" style="margin:0;">
                        <textarea rows="5" :name="'agents['+i+'][positive]'" x-model="agent.positive"
                                  placeholder="- Orienter vers l’espace participant…"></textarea>
                    </div>

                    <h3 style="font-size:14px;font-weight:800;margin:16px 0 8px;color:#991b1b;">Instructions NÉGATIVES — à ne jamais faire</h3>
                    <div class="ukc-field" style="margin:0;">
                        <textarea rows="5" :name="'agents['+i+'][negative]'" x-model="agent.negative"
                                  placeholder="- Ne jamais inventer de tarifs ni de décisions…"></textarea>
                    </div>

                    <div class="ukc-form-actions" style="justify-content:space-between;">
                        <button type="submit" class="ukc-btn ukc-btn--blue">
                            <x-ukc.icon name="check" :size="18" /> Enregistrer les agents
                        </button>
                        <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" x-show="agents.length > 1"
                                @click="if (confirm('Supprimer cet agent ?')) removeAgent(i)">
                            <x-ukc.icon name="trash" :size="16" /> Supprimer cet agent
                        </button>
                    </div>
                </div>
            </template>
        </form>
    </div>
</x-layouts.admin>
