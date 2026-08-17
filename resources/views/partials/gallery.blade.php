{{--
    Grille de galerie : 7 vignettes visibles max ; au-delà, la 8ᵉ case devient
    « +N / Voir plus » (ouvre le lightbox au 8ᵉ média). $media : liste
    d'éléments { type: 'image'|'video', src, caption, thumb }. Le lightbox lui-même
    est dans partials.lightbox ; la logique est dans public.js.
--}}
@php($visible = 7)
<div class="gallery-grid" id="gallery" data-gallery>
    @foreach (array_slice($media, 0, $visible) as $i => $m)
        <button type="button" class="gallery-item {{ $m['type'] === 'video' ? 'is-video' : '' }}" data-idx="{{ $i }}">
            @if (! empty($m['thumb']))
                <img src="{{ $m['thumb'] }}" alt="{{ $m['caption'] }}" loading="lazy">
            @endif
            @if ($m['type'] === 'video')<span class="play-badge">▶</span>@endif
            @if (! empty($m['caption']))<span class="cap">{{ $m['caption'] }}</span>@endif
        </button>
    @endforeach

    @if (count($media) > $visible)
        @php($more = $media[$visible])
        <button type="button" class="gallery-item is-more" id="galleryMore" data-start="{{ $visible }}">
            @if (! empty($more['thumb']))<img src="{{ $more['thumb'] }}" alt="">@endif
            <span class="more-count">+{{ count($media) - $visible }}</span>
            <span class="more-label">Voir plus</span>
        </button>
    @endif
</div>

<script type="application/json" id="galleryData">@json($media)</script>
