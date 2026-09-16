{{-- Groupe de mise en page : cases à cocher (afficher) + libellé personnalisable.
     Params : $title, $field, $catalog (key=>libellé), $selected (list de clés), $labels (map). --}}
<div class="ukc-field" style="margin-top:14px;">
    <label style="font-weight:700;">{{ $title }}</label>
    <p style="font-size:12px;color:var(--ukc-muted);margin:2px 0 8px;">Cochez les éléments à afficher ; renommez-les si besoin.</p>
    @foreach ($catalog as $key => $default)
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:6px;min-width:160px;font-weight:500;">
                <input type="checkbox" name="{{ $field }}[]" value="{{ $key }}" style="width:auto;padding:0;" @checked(in_array($key, $selected, true))>
                {{ $default }}
            </label>
            <input type="text" name="{{ $field }}_label[{{ $key }}]" value="{{ $labels[$key] ?? '' }}" placeholder="{{ $default }}" style="flex:1;min-width:160px;">
        </div>
    @endforeach
</div>
