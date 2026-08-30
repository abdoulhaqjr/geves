@php
    // Agrège les messages flash « status » (succès) et « error » en toasts.
    $toasts = [];
    foreach (['ukc_status' => 'ok', 'status' => 'ok', 'ukc_error' => 'err', 'error' => 'err'] as $key => $type) {
        if (session()->has($key) && is_string(session($key)) && session($key) !== '') {
            $toasts[] = ['type' => $type, 'msg' => session($key)];
        }
    }
@endphp
@if (count($toasts))
    <div class="ukc-toasts" x-data="ukcToasts(@js($toasts))" aria-live="polite" aria-atomic="true">
        <template x-for="t in items" :key="t.id">
            <div class="ukc-toast" :class="'ukc-toast--' + t.type"
                 x-show="t.show"
                 x-transition.opacity.duration.300ms
                 role="status">
                <span class="ukc-toast__ico" aria-hidden="true">
                    <template x-if="t.type === 'ok'">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    </template>
                    <template x-if="t.type !== 'ok'">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2 1 21h22L12 2zm1 14h-2v2h2v-2zm0-6h-2v4h2v-4z"/></svg>
                    </template>
                </span>
                <span class="ukc-toast__msg" x-text="t.msg"></span>
                <button type="button" class="ukc-toast__close" @click="dismiss(t.id)" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
        </template>
    </div>
@endif
