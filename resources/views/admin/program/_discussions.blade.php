{{-- Onglet « Discussions » du module Programme : le fil de chaque séance ayant une
     visio ou des messages, consultable après la visio ET après l'événement. --}}
<div class="ukc-card" style="margin-top:16px;">
    <h2 style="justify-content:flex-start;gap:8px;">
        <x-ukc.icon name="message" :size="18" /> Fils de discussion des séances
    </h2>
    <p style="font-size:13px;color:var(--ukc-muted);margin:-4px 0 12px;">
        Le fil échangé dans la salle de visio de chaque séance, archivé et consultable même après l'événement.
    </p>

    @if (empty($discussions))
        <div class="ukc-empty">
            <span class="ico"><x-ukc.icon name="message" :size="28" /></span>
            <h3>Aucune discussion</h3>
            <p>Les séances dotées d'une visio apparaîtront ici, avec leur fil de discussion dès qu'un message y est posté.</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="ukc-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left;">Séance</th>
                        <th style="text-align:left;">Jour / salle</th>
                        <th style="text-align:left;">Modérateur</th>
                        <th style="text-align:left;">Visio</th>
                        <th style="text-align:left;width:1%;white-space:nowrap;">Messages</th>
                        <th style="text-align:right;width:1%;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($discussions as $row)
                        @php($s = $row['session'])
                        <tr>
                            <td><strong>{{ $s->label }}</strong></td>
                            <td style="font-size:13px;color:var(--ukc-muted);">
                                @if ($s->starts_at){{ $s->starts_at->translatedFormat('D j M - H:i') }}@elseif ($s->session_day){{ $s->session_day->translatedFormat('D j M') }}@endif
                                @if ($s->room) - {{ $s->room }}@endif
                            </td>
                            <td style="font-size:13px;color:var(--ukc-muted);">{{ $s->moderator_email ?: '-' }}</td>
                            <td>
                                <span class="ukc-pill ukc-pill--{{ $s->meetingStatusEnum()->pill() }}">{{ $s->meetingStatusEnum()->label() }}</span>
                            </td>
                            <td style="white-space:nowrap;">
                                @if ($row['count'] > 0)
                                    <strong>{{ $row['count'] }}</strong>
                                    @if ($row['lastAt'])<div style="font-size:12px;color:var(--ukc-muted);">dernier {{ $row['lastAt']->translatedFormat('j M H:i') }}</div>@endif
                                @else
                                    <span style="color:var(--ukc-muted);">-</span>
                                @endif
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                @if ($row['count'] > 0)
                                    <a href="{{ route('admin.program.discussions.show', $s) }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                        Voir le fil
                                    </a>
                                @else
                                    <span style="font-size:12.5px;color:var(--ukc-muted);">Aucun message</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
