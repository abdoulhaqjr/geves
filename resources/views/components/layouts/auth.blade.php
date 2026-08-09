@props(['title' => null])
<x-layouts.app :title="$title">
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 16px;background:
                radial-gradient(1200px 600px at 50% -10%, rgba(34,62,164,.10), transparent 60%), var(--ukc-bg);">
        <div style="width:100%;max-width:440px;">
            <div style="text-align:center;margin-bottom:22px;">
                <a href="{{ url('/') }}" style="display:inline-block;">
                    <div style="font-size:1.25rem;font-weight:900;color:var(--ukc-blue);letter-spacing:-.01em;">
                        Colloques scientifiques
                    </div>
                    <div style="font-size:.85rem;color:var(--ukc-muted);font-weight:600;">Université de Kara</div>
                </a>
            </div>

            <div class="ukc-card" style="padding:28px 26px;position:relative;overflow:hidden;">
                {{-- Accent tricolore --}}
                <div style="position:absolute;top:0;left:0;right:0;height:4px;display:flex;">
                    <span style="flex:1;background:var(--ukc-blue);"></span>
                    <span style="flex:1;background:var(--ukc-gold);"></span>
                    <span style="flex:1;background:var(--ukc-green);"></span>
                </div>

                @isset($heading)
                    <div style="margin:6px 0 20px;">
                        <h1 style="font-size:1.35rem;font-weight:800;color:var(--ukc-text);">{{ $heading }}</h1>
                        @isset($subheading)
                            <p style="font-size:.9rem;color:var(--ukc-muted);margin-top:4px;">{{ $subheading }}</p>
                        @endisset
                    </div>
                @endisset

                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.app>
