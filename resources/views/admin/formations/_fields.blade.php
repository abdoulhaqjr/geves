@php
    $val = fn ($k, $d = '') => old($k, $f->$k ?? $d);
    $dt = fn ($k) => $f && $f->$k ? $f->$k->format('Y-m-d\TH:i') : old($k);
    $grid = 'display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px;';
@endphp

{{-- Bannière (image, cover) - tout en haut du formulaire, sans nom de fichier --}}
<div class="ukc-form-row" x-data="{ preview: @js($f?->flyerUrl()), removed: false,
        pick(e) { const fl = e.target.files[0]; if (fl) { this.preview = URL.createObjectURL(fl); this.removed = false; } },
        drop(e) { const fl = e.dataTransfer.files[0]; if (fl && fl.type.indexOf('image/') === 0) { this.$refs.bin.files = e.dataTransfer.files; this.preview = URL.createObjectURL(fl); this.removed = false; } },
        remove() { this.preview = ''; this.removed = true; this.$refs.bin.value = ''; } }">
    <label>Bannière (image PNG ou JPG)</label>
    <div style="position:relative;aspect-ratio:16/6;border:2px dashed var(--ukc-border,#cbd5e1);border-radius:10px;overflow:hidden;background:#f8f9fc;cursor:pointer;"
         @dragover.prevent @drop.prevent="drop($event)" @click="!preview && $refs.bin.click()">
        <template x-if="preview">
            <img :src="preview" alt="Bannière" style="width:100%;height:100%;object-fit:cover;display:block;">
        </template>
        <template x-if="!preview">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:6px;color:var(--ukc-muted);">
                <x-ukc.icon name="upload" :size="26" />
                <span style="font-size:12.5px;">Cliquez ou glissez une image (PNG/JPG)</span>
            </div>
        </template>
        {{-- x-show sur le wrapper : sinon Alpine remet display:'' et casse le flex --}}
        <div x-show="preview" style="position:absolute;top:8px;right:8px;">
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button type="button" title="Changer l'image" @click.stop="$refs.bin.click()" style="background:rgba(255,255,255,.92);border:none;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ukc-blue,#223ea4);box-shadow:0 1px 3px rgba(0,0,0,.2);"><x-ukc.icon name="pen" :size="15" /></button>
                <button type="button" title="Retirer" @click.stop="remove()" style="background:rgba(255,255,255,.92);border:none;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#dc2626;box-shadow:0 1px 3px rgba(0,0,0,.2);"><x-ukc.icon name="trash" :size="15" /></button>
            </div>
        </div>
    </div>
    <input type="file" x-ref="bin" name="flyer" accept="image/png,image/jpeg" @change="pick($event)" style="display:none;">
    <input type="hidden" name="flyer_remove" :value="removed ? '1' : '0'">
</div>

<div class="ukc-form-row">
    <label>Titre / thème de la formation <span class="req">*</span></label>
    <input type="text" name="title" value="{{ $val('title') }}" maxlength="200" required placeholder="Ex. Rédaction scientifique et publication">
    @error('title') <span class="error">{{ $message }}</span> @enderror
</div>
<div class="ukc-form-row">
    <label>Sous-thème (facultatif)</label>
    <input type="text" name="theme" value="{{ $val('theme') }}" maxlength="255">
</div>
<div class="ukc-form-row">
    <label>Objectif</label>
    <textarea name="objective" rows="3" maxlength="5000" placeholder="Objectifs pédagogiques de la formation…">{{ $val('objective') }}</textarea>
</div>
<div style="{{ $grid }}">
    <div class="ukc-form-row"><label>Lieu</label><input type="text" name="location" value="{{ $val('location') }}" maxlength="200"></div>
    <div class="ukc-form-row"><label>Salle</label><input type="text" name="room" value="{{ $val('room') }}" maxlength="120"></div>
</div>
<div style="{{ $grid }}">
    <div class="ukc-form-row">
        <label>Frais (0 = gratuite)</label>
        <input type="number" name="fee" value="{{ $f ? (float) $f->fee : old('fee', 0) }}" min="0" step="0.01">
        @error('fee') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="ukc-form-row"><label>Places (facultatif)</label><input type="number" name="capacity" value="{{ $val('capacity') }}" min="1"></div>
</div>
<div style="{{ $grid }}">
    <div class="ukc-form-row"><label>Début</label><input type="datetime-local" name="starts_at" value="{{ $dt('starts_at') }}"></div>
    <div class="ukc-form-row">
        <label>Fin</label>
        <input type="datetime-local" name="ends_at" value="{{ $dt('ends_at') }}">
        @error('ends_at') <span class="error">{{ $message }}</span> @enderror
    </div>
</div>

<div style="{{ $grid }}margin-top:2px;">
    <label class="ukc-check" style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="published" value="1" @checked($f ? $f->published : old('published'))> <span>Formation <strong>active</strong></span></label>
    <label class="ukc-check" style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="public" value="1" @checked($f ? $f->public : old('public'))> <span>Sur la <strong>page publique</strong></span></label>
</div>
