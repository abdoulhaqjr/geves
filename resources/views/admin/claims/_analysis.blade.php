{{--
    Une analyse de traitement (IA). Sortie en partial pour être rendue à
    l'identique dépliée (la plus récente) et repliée (les précédentes) : deux
    copies du même balisage finiraient par diverger, et c'est l'ancienne qu'on
    oublierait de corriger.

    Reçoit : $a (l'analyse), $claim.
--}}
@php($verdict = $a['verdict'] ?? 'procedure')

@if (! empty($a['summary']))<div style="margin-top:8px;line-height:1.55;">{{ $a['summary'] }}</div>@endif

@if ($verdict === 'resolvable' && filled($a['resolution'] ?? ''))
    <div style="margin-top:8px;background:var(--ukc-bg);border-radius:10px;padding:10px 12px;white-space:pre-line;line-height:1.55;">{{ $a['resolution'] }}</div>
    <form method="POST" action="{{ route('admin.claims.status', $claim) }}">
        @csrf
        <input type="hidden" name="status" value="resolved">
        <input type="hidden" name="resolution" value="{{ $a['resolution'] }}">
        <p style="font-size:12px;color:var(--ukc-muted);margin:8px 0 0;">La résolution proposée sera envoyée au déclarant par e-mail.</p>
        <div class="ukc-form-actions ukc-form-actions--plain">
            <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="15" /> Utiliser cette résolution et marquer résolue</button>
        </div>
    </form>
@elseif ($verdict === 'procedure' && ! empty($a['steps']))
    <ol style="margin:8px 0 0;padding-left:18px;display:flex;flex-direction:column;gap:5px;line-height:1.5;">
        @foreach ($a['steps'] as $step)<li>{{ $step }}</li>@endforeach
    </ol>
@elseif ($verdict === 'unresolvable' && filled($a['reason'] ?? ''))
    <div style="margin-top:8px;white-space:pre-line;line-height:1.55;">{{ $a['reason'] }}</div>
@endif
