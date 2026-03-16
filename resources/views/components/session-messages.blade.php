@props([
    'keys' => ['success', 'status', 'error', 'warning', 'info', 'message'],
    'dismiss' => true,
    'timeout' => 5000,
    'showValidation' => true,
])

@php
    /**
     * Normalize different possible session value types into an array of strings.
     *
     * @param mixed $val
     * @return array<int, string>
     */
    $toArray = static function ($val): array {
        if ($val instanceof \Illuminate\Support\MessageBag) {
            return $val->all();
        }

        if (is_array($val)) {
            return $val;
        }

        if (is_string($val)) {
            return [$val];
        }

        if (is_object($val) && method_exists($val, '__toString')) {
            return [(string) $val];
        }

        return [];
    };

    /**
     * Resolve a given key to a canonical alert type.
     *
     * @param string $key
     * @return 'success'|'error'|'warning'|'info'
     */
    $detectType = static function ($key): string {
        $k = strtolower((string) $key);

        static $map = [
            'success' => 'success',
            'status'  => 'success',
            'ok'      => 'success',

            'error'   => 'error',
            'danger'  => 'error',
            'fail'    => 'error',
            'failed'  => 'error',

            'warning' => 'warning',
            'warn'    => 'warning',
        ];

        return $map[$k] ?? 'info';
    };

    // Normalize $keys to an array if provided as a string.
    $keys = is_array($keys)
        ? $keys
        : preg_split('/[\s,]+/', (string) $keys, -1, PREG_SPLIT_NO_EMPTY);

    $items = [];

    foreach ($keys as $key) {
        if (session()->has($key)) {
            foreach ($toArray(session()->get($key)) as $text) {
                $items[] = ['type' => $detectType($key), 'text' => $text];
            }
        }
    }

    if ($showValidation && isset($errors) && $errors->any()) {
        foreach ($errors->all() as $text) {
            $items[] = ['type' => 'error', 'text' => $text];
        }
    }

    $timeoutAttr = ($dismiss && $timeout) ? (int) $timeout : 0;
@endphp

@once
    <style>
        .xm-alert {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            padding: .75rem 1rem;
            border: 1px solid;
            border-radius: .5rem;
            margin: .5rem 0;
            font-size: .95rem;
            line-height: 1.3;
            background: #fff;
        }

        .xm-alert--success { border-color: #16a34a33; color: #166534; background: #16a34a0d; }
        .xm-alert--error   { border-color: #dc262633; color: #7f1d1d; background: #dc26260d; }
        .xm-alert--warning { border-color: #d9770633; color: #7c2d12; background: #d977060d; }
        .xm-alert--info    { border-color: #2563eb33; color: #1e3a8a; background: #2563eb0d; }

        .xm-alert__icon { flex: 0 0 auto; margin-top: .15rem; }
        .xm-alert__content { flex: 1 1 auto; }

        .xm-alert__close {
            position: absolute;
            top: .35rem;
            right: .5rem;
            background: transparent;
            border: 0;
            color: inherit;
            opacity: .7;
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
            padding: .15rem;
            border-radius: .25rem;
        }

        .xm-alert__close:hover { opacity: 1; }
        .xm-alert__close:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }

        .xm-alert__list { margin: .25rem 0 0 .95rem; padding: 0; }
    </style>
    <script>
        (function () {
            function closeAlert(el) {
                if (!el || el.__closing) return;
                el.__closing = true;

                var currentHeight = el.offsetHeight;
                el.style.height = currentHeight + 'px';

                requestAnimationFrame(function () {
                    el.style.transition = 'height .2s ease, opacity .2s ease';
                    el.style.opacity = '0';
                    el.style.height = '0';

                    setTimeout(function () {
                        if (el && el.parentNode) {
                            el.parentNode.removeChild(el);
                        }
                    }, 220);
                });
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest && e.target.closest('[data-xm-close]');
                if (btn) {
                    closeAlert(btn.closest('.xm-alert'));
                }
            });

            window.addEventListener('load', function () {
                var alerts = document.querySelectorAll('.xm-alert[data-timeout]');
                alerts.forEach(function (el) {
                    var ms = parseInt(el.getAttribute('data-timeout'), 10);
                    if (ms > 0) {
                        setTimeout(function () { closeAlert(el); }, ms);
                    }
                });
            });
        })();
    </script>
@endonce

@if (count($items))
    <div class="xm-alerts">
        @foreach ($items as $item)
            @php
                $type = in_array($item['type'], ['success', 'error', 'warning', 'info'], true) ? $item['type'] : 'info';
            @endphp

            <div
                class="xm-alert xm-alert--{{ $type }}"
                role="alert"
                aria-live="polite"
                data-timeout="{{ $timeoutAttr }}"
            >
                <div class="xm-alert__icon" aria-hidden="true">
                    @if ($type === 'success')
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    @elseif ($type === 'error')
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 9l-6 6m0-6l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    @elseif ($type === 'warning')
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 9v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    @else
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 8h.01M11 12h1v4h1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    @endif
                </div>

                <div class="xm-alert__content">
                    {{ $item['text'] }}
                </div>

                @if ($dismiss)
                    <button type="button" class="xm-alert__close" title="Dismiss" aria-label="Dismiss" data-xm-close>&times;</button>
                @endif
            </div>
        @endforeach
    </div>
@endif