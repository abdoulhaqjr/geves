<div class="admin-page admin-doc-designer">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
        <div class="ukc-field" style="min-width:200px;margin:0;">
            <label>Édition</label>
            <select wire:model.live="edition_id">
                <option value="">— Choisir —</option>
                @foreach ($editions as $ed)<option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>@endforeach
            </select>
        </div>
        <div class="ukc-field" style="min-width:150px;margin:0;">
            <label>Type de document</label>
            <select wire:model.live="kind">
                <option value="attestation">Attestation</option>
                <option value="badge">Badge</option>
            </select>
        </div>
        @if ($kind === 'attestation')
            <div class="ukc-field" style="min-width:160px;margin:0;">
                <label>Attestation</label>
                <select wire:model.live="type">
                    @foreach ($attestationTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        @endif
        @error('edition_id') <span class="error" style="align-self:center;">{{ $message }}</span> @enderror
    </div>

    <div class="doc-designer-grid">
        {{-- Éditeur visuel (Alpine, isolé de Livewire) --}}
        <div class="ukc-card" style="padding:0;overflow:hidden;" wire:ignore
             x-data="docEditor({ layout: @js($layout), vars: @js($vars) })">
            <div class="doc-toolbar">
                <button type="button" class="dt-btn" @click="addElement('text')"><x-ukc.icon name="pen" :size="15" /> Texte</button>
                <button type="button" class="dt-btn" @click="addElement('qr')"><x-ukc.icon name="grid" :size="15" /> QR</button>
                <button type="button" class="dt-btn" @click="addElement('rect')"><x-ukc.icon name="label" :size="15" /> Bandeau</button>
                <button type="button" class="dt-btn" @click="addElement('image')"><x-ukc.icon name="folder" :size="15" /> Image</button>
                <span class="dt-sep"></span>
                <button type="button" class="dt-btn" @click="moveLayer(1)" :disabled="selectedIndex===null" title="Avancer">▲</button>
                <button type="button" class="dt-btn" @click="moveLayer(-1)" :disabled="selectedIndex===null" title="Reculer">▼</button>
                <button type="button" class="dt-btn dt-btn--danger" @click="deleteSelected()" :disabled="selectedIndex===null"><x-ukc.icon name="trash" :size="15" /></button>
                <span style="margin-left:auto;"></span>
                <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="save()">
                    <x-ukc.icon name="check" :size="15" /> Enregistrer
                </button>
            </div>

            <div class="doc-editor-body">
                {{-- Canvas --}}
                <div class="doc-canvas-wrap" x-ref="canvasWrap" :style="`height:${wrapHeight}px`" @mousedown.self="deselect()">
                    <div class="doc-canvas"
                         :style="`width:${layout.width}px;height:${layout.height}px;background:${layout.bg};transform:scale(${scale});`"
                         @mousedown.self="deselect()">
                        <template x-for="(el, i) in layout.elements" :key="i">
                            <div class="doc-el" :class="{ selected: selectedIndex === i }" :style="elStyle(el)"
                                 @mousedown="startDrag($event, i)">
                                <template x-if="el.type === 'text'">
                                    <div class="doc-fill doc-text" :style="textStyle(el)" x-text="substitute(el.text)"></div>
                                </template>
                                <template x-if="el.type === 'rect'">
                                    <div class="doc-fill" :style="`background:${el.color};`"></div>
                                </template>
                                <template x-if="el.type === 'image'">
                                    <template x-if="el.src"><img :src="el.src" class="doc-fill" style="object-fit:contain;" alt=""></template>
                                </template>
                                <template x-if="el.type === 'image' && !el.src">
                                    <div class="doc-fill doc-ph">Image</div>
                                </template>
                                <template x-if="el.type === 'qr'">
                                    <div class="doc-fill doc-qr">QR</div>
                                </template>
                                <div class="doc-handle" x-show="selectedIndex === i" @mousedown="startResize($event, i)"></div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Panneau de propriétés --}}
                <div class="doc-props">
                    {{-- Réglages du canevas (rien de sélectionné) --}}
                    <template x-if="!selected">
                        <div>
                            <h4>Canevas</h4>
                            <div class="dp-row">
                                <label>Largeur</label>
                                <input type="number" x-model.number="layout.width" min="120" max="2000" @change="fit()">
                            </div>
                            <div class="dp-row">
                                <label>Hauteur</label>
                                <input type="number" x-model.number="layout.height" min="120" max="2000" @change="fit()">
                            </div>
                            <div class="dp-row">
                                <label>Fond</label>
                                <input type="color" x-model="layout.bg">
                            </div>
                            <div class="dp-row">
                                <label>Accent</label>
                                <input type="color" :value="layout.accent" @input="applyAccent($event.target.value)">
                            </div>
                            <p class="dp-hint">Cliquez un élément sur le canevas pour l’éditer. Glissez pour déplacer, tirez la poignée pour redimensionner.</p>
                        </div>
                    </template>

                    {{-- Propriétés de l'élément sélectionné --}}
                    <template x-if="selected">
                        <div>
                            <h4 x-text="{ text:'Texte', qr:'QR code', rect:'Bandeau', image:'Image' }[selected.type]"></h4>

                            <template x-if="selected.type === 'text'">
                                <div>
                                    <div class="dp-row dp-col">
                                        <label>Contenu</label>
                                        <textarea x-model="selected.text" rows="2"></textarea>
                                    </div>
                                    <div class="dp-vars">
                                        @foreach ($variables as $token => $label)
                                            <button type="button" class="dp-var" title="{{ $label }}" @click="insertVar('{{ $token }}')">{{ $token }}</button>
                                        @endforeach
                                    </div>
                                    <div class="dp-grid2">
                                        <div class="dp-row"><label>Taille</label><input type="number" x-model.number="selected.size" min="6" max="140"></div>
                                        <div class="dp-row"><label>Graisse</label>
                                            <select x-model.number="selected.weight">
                                                <option value="400">Normal</option><option value="600">Semi-gras</option>
                                                <option value="700">Gras</option><option value="800">Extra</option><option value="900">Black</option>
                                            </select>
                                        </div>
                                        <div class="dp-row"><label>Couleur</label><input type="color" x-model="selected.color"></div>
                                        <div class="dp-row"><label>Alignement</label>
                                            <select x-model="selected.align"><option value="left">Gauche</option><option value="center">Centré</option><option value="right">Droite</option></select>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="selected.type === 'rect'">
                                <div class="dp-row"><label>Couleur</label><input type="color" x-model="selected.color"></div>
                            </template>

                            <template x-if="selected.type === 'image'">
                                <div class="dp-row dp-col"><label>URL de l’image</label><input type="url" x-model="selected.src" placeholder="https://…/logo.png"></div>
                            </template>

                            <template x-if="selected.type === 'qr'">
                                <div class="dp-row dp-col"><label>Valeur encodée</label><input type="text" x-model="selected.value" placeholder="{verify_url}"></div>
                            </template>

                            {{-- Position / taille communes --}}
                            <div class="dp-grid2" style="margin-top:8px;">
                                <div class="dp-row"><label>X</label><input type="number" x-model.number="selected.x" min="0"></div>
                                <div class="dp-row"><label>Y</label><input type="number" x-model.number="selected.y" min="0"></div>
                                <template x-if="selected.type === 'qr'">
                                    <div class="dp-row"><label>Taille</label><input type="number" x-model.number="selected.size" min="24" max="400"></div>
                                </template>
                                <template x-if="selected.type !== 'qr'">
                                    <div class="dp-row"><label>Largeur</label><input type="number" x-model.number="selected.w" min="4"></div>
                                </template>
                                <template x-if="selected.type !== 'qr'">
                                    <div class="dp-row"><label>Hauteur</label><input type="number" x-model.number="selected.h" min="4"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <p class="doc-note">Aperçu avec des données d’exemple. Le QR de vérification est réel au rendu final du document.</p>
        </div>

        {{-- Colonne latérale : génération IA (Livewire) --}}
        <div style="display:flex;flex-direction:column;gap:16px;">
            @if ($aiAvailable)
                <div class="ukc-card" style="background:linear-gradient(135deg,rgba(34,62,164,.05),rgba(0,153,51,.05));">
                    <h2><span><x-ukc.icon name="sparkles" :size="18" /> Générer par IA</span></h2>
                    <p class="desc">L’IA propose une mise en page complète ; ajustez-la ensuite au glisser-déposer.</p>
                    <div class="ukc-field">
                        <label for="prompt">Consigne de style</label>
                        <textarea id="prompt" wire:model="prompt" rows="3"
                                  placeholder="Ex. sobre et institutionnel, dominante bleu Université de Kara, titre centré, QR en bas à droite."></textarea>
                        @error('prompt') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label for="inspiration">Image d’inspiration (optionnel)</label>
                        <input type="file" id="inspiration" wire:model="inspiration" accept=".png,.jpg,.jpeg,.webp">
                        <div wire:loading wire:target="inspiration" class="hint">Téléversement…</div>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" wire:click="generate"
                            wire:loading.attr="disabled" wire:target="generate,inspiration">
                        <span wire:loading.remove wire:target="generate"><x-ukc.icon name="sparkles" :size="16" /> Générer la mise en page</span>
                        <span wire:loading wire:target="generate">Génération…</span>
                    </button>
                </div>
            @else
                <div class="ukc-alert ukc-alert--info">
                    <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                    <div>Configurez un fournisseur d’IA dans les <a href="{{ route('admin.settings') }}" wire:navigate style="text-decoration:underline;">Réglages</a> pour la génération assistée. L’éditeur visuel reste disponible.</div>
                </div>
            @endif

            <div class="ukc-card">
                <h2><span><x-ukc.icon name="info" :size="18" /> Aide</span></h2>
                <ul style="font-size:.85rem;color:var(--ukc-muted);line-height:1.7;margin:0;padding-left:18px;">
                    <li>Ajoutez des éléments avec la barre d’outils.</li>
                    <li>Glissez pour déplacer, tirez la poignée bleue pour redimensionner.</li>
                    <li>Dans un texte, insérez des variables comme <code>{name}</code> ou <code>{ref}</code>.</li>
                    <li>« Avancer / Reculer » change l’ordre de superposition.</li>
                    <li>Le modèle s’applique à l’édition et au type sélectionnés.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
