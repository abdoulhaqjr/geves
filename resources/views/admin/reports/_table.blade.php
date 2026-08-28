{{-- Table générique d'un rapport : $title, $headers (list<string>), $rows (list<list<scalar>>). --}}
<div class="ukc-card" style="padding:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 18px;border-bottom:1px solid var(--ukc-border);">
        <h2 style="margin:0;">{{ $title }}</h2>
        <span class="ukc-pill ukc-pill--muted">{{ count($rows) }} ligne(s)</span>
    </div>
    @if (count($rows))
        <div class="ukc-table-wrap">
            <table class="ukc-table">
                <thead>
                    <tr>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{{ is_float($cell) ? number_format($cell, 0, ',', ' ') : $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="ukc-empty" style="padding:28px;"><p style="margin:0;">Aucune donnée pour cette édition.</p></div>
    @endif
</div>
