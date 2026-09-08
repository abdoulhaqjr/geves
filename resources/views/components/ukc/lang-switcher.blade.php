@php
    $locales = \App\Support\Locales::available();
    $current = app()->getLocale();
@endphp
@if (count($locales) > 1)
    <div class="ukc-lang" style="display:inline-flex;align-items:center;gap:2px;border:1px solid var(--ukc-border);border-radius:999px;padding:2px;">
        @foreach ($locales as $code => $label)
            <a href="{{ route('locale.switch', $code) }}"
               title="{{ $label }}"
               @if ($code === $current) aria-current="true" @endif
               style="text-decoration:none;font-size:.78rem;font-weight:700;letter-spacing:.03em;padding:3px 9px;border-radius:999px;
                      {{ $code === $current
                          ? 'background:var(--ukc-blue,#223ea4);color:#fff;'
                          : 'background:transparent;color:var(--ukc-muted,#667085);' }}">
                {{ strtoupper($code) }}
            </a>
        @endforeach
    </div>
@endif
