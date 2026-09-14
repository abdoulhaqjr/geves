@if ($paginator->hasPages())
    <nav class="ukc-pagination" role="navigation" aria-label="{{ __('Pagination') }}">
        <div class="ukc-pagination__info">
            @if ($paginator->total() > 0)
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} <span>sur</span> {{ $paginator->total() }}
            @endif
        </div>

        <ul class="ukc-pagination__list">
            {{-- Précédent --}}
            @if ($paginator->onFirstPage())
                <li class="is-disabled" aria-disabled="true"><span aria-hidden="true">‹</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Précédent') }}">‹</a></li>
            @endif

            {{-- Numéros de page --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="is-dots" aria-disabled="true"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="is-active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Suivant --}}
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Suivant') }}">›</a></li>
            @else
                <li class="is-disabled" aria-disabled="true"><span aria-hidden="true">›</span></li>
            @endif
        </ul>
    </nav>
@endif
