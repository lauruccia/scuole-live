@php
    /** @var array $old */
    /** @var array $new */
    $old = $old ?? [];
    $new = $new ?? [];

    // Maschera valori sensibili che non dovrebbero MAI apparire
    $maskKeys = ['password', 'remember_token', 'signature_otp', 'stripe_payment_intent', 'paypal_order_id'];
    $mask = function ($key, $value) use ($maskKeys) {
        if (in_array($key, $maskKeys, true) && $value !== null && $value !== '') {
            return '••••••••';
        }
        return $value;
    };

    // Tutti i campi che compaiono in old o new
    $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
    sort($keys);

    $format = function ($value) {
        if (is_null($value)) return '—';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        return (string) $value;
    };
@endphp

@if (empty($keys))
    <div class="text-sm text-gray-500 dark:text-gray-400 italic">
        Nessuna differenza registrata (es. evento "created" senza valori "old", o "deleted" senza valori "new").
    </div>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800">
                    <th class="text-left px-3 py-2 font-semibold border-b border-gray-200 dark:border-gray-700 w-1/4">Campo</th>
                    <th class="text-left px-3 py-2 font-semibold border-b border-gray-200 dark:border-gray-700">Prima</th>
                    <th class="text-left px-3 py-2 font-semibold border-b border-gray-200 dark:border-gray-700">Dopo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($keys as $key)
                    @php
                        $oldVal = $mask($key, $old[$key] ?? null);
                        $newVal = $mask($key, $new[$key] ?? null);
                        $changed = ($old[$key] ?? null) !== ($new[$key] ?? null);
                    @endphp
                    <tr class="{{ $changed ? 'bg-amber-50/40 dark:bg-amber-900/10' : '' }} border-b border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 font-mono text-xs align-top text-gray-700 dark:text-gray-300">
                            {{ $key }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            <pre class="whitespace-pre-wrap break-words text-xs {{ $changed ? 'text-rose-700 dark:text-rose-300 line-through decoration-rose-400/60' : 'text-gray-500 dark:text-gray-400' }}">{{ $format($oldVal) }}</pre>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <pre class="whitespace-pre-wrap break-words text-xs {{ $changed ? 'text-emerald-700 dark:text-emerald-300 font-medium' : 'text-gray-500 dark:text-gray-400' }}">{{ $format($newVal) }}</pre>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
