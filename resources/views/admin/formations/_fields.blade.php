@php
    $val = fn ($k, $d = '') => old($k, $f->$k ?? $d);
    $dt = fn ($k) => $f && $f->$k ? $f->$k->format('Y-m-d\TH:i') : old($k);
@endphp

<div class="ukc-field">
    <label>Titre / thème de la formation <span class="req">*</span></label>
    <input type="text" name="title" value="{{ $val('title') }}" maxlength="200" required placeholder="Ex. Rédaction scientifique et publication">
    @error('title') <span class="error">{{ $message }}</span> @enderror
</div>
<div class="ukc-field">
    <label>Sous-thème (facultatif)</label>
    <input type="text" name="theme" value="{{ $val('theme') }}" maxlength="255">
</div>
<div class="ukc-field">
    <label>Objectif</label>
    <textarea name="objective" rows="3" maxlength="5000" placeholder="Objectifs pédagogiques de la formation…">{{ $val('objective') }}</textarea>
</div>
<div style="display:flex;gap:10px;flex-wrap:wrap;">
    <div class="ukc-field" style="flex:1;min-width:150px;">
        <label>Lieu</label>
        <input type="text" name="location" value="{{ $val('location') }}" maxlength="200">
    </div>
    <div class="ukc-field" style="flex:1;min-width:120px;">
        <label>Salle</label>
        <input type="text" name="room" value="{{ $val('room') }}" maxlength="120">
    </div>
</div>
<div style="display:flex;gap:10px;flex-wrap:wrap;">
    <div class="ukc-field" style="flex:1;min-width:120px;">
        <label>Frais (0 = gratuite)</label>
        <input type="number" name="fee" value="{{ $f ? (float) $f->fee : old('fee', 0) }}" min="0" step="0.01">
        @error('fee') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="ukc-field" style="flex:1;min-width:100px;">
        <label>Places (facultatif)</label>
        <input type="number" name="capacity" value="{{ $val('capacity') }}" min="1">
    </div>
</div>
<div style="display:flex;gap:10px;flex-wrap:wrap;">
    <div class="ukc-field" style="flex:1;min-width:150px;">
        <label>Début</label>
        <input type="datetime-local" name="starts_at" value="{{ $dt('starts_at') }}">
    </div>
    <div class="ukc-field" style="flex:1;min-width:150px;">
        <label>Fin</label>
        <input type="datetime-local" name="ends_at" value="{{ $dt('ends_at') }}">
        @error('ends_at') <span class="error">{{ $message }}</span> @enderror
    </div>
</div>
<label class="ukc-check" style="display:flex;align-items:center;gap:8px;margin:4px 0;cursor:pointer;">
    <input type="checkbox" name="published" value="1" @checked($f ? $f->published : old('published'))>
    <span>Formation <strong>active</strong> (sinon brouillon)</span>
</label>
<label class="ukc-check" style="display:flex;align-items:center;gap:8px;margin:4px 0 14px;cursor:pointer;">
    <input type="checkbox" name="public" value="1" @checked($f ? $f->public : old('public'))>
    <span>Afficher sur la <strong>page publique</strong> de l’événement</span>
</label>
