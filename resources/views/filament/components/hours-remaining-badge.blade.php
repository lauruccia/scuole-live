@php
    /** @var \App\Models\Lesson|null $record */
    $contract = $record?->contract;

    $purchased = (float) ($contract?->hours_purchased ?? 0);
    $consumed  = (float) ($contract?->hours_consumed ?? 0);
    $remaining = $purchased - $consumed;

    $remainingSafe = max($remaining, 0);

    $label = rtrim(rtrim(number_format($remainingSafe, 2, ',', ''), '0'), ',');

    // colori:
    // 0 => rosso
    // <=2 => giallo
    // >2 => verde
    if ($remainingSafe <= 0) {
        $classes = 'bg-red-50 text-red-700 ring-red-200';
        $text = "0 ore";
    } elseif ($remainingSafe <= 2) {
        $classes = 'bg-amber-50 text-amber-800 ring-amber-200';
        $text = "{$label} ore";
    } else {
        $classes = 'bg-emerald-50 text-emerald-800 ring-emerald-200';
        $text = "{$label} ore";
    }
@endphp

<div class="w-full">
    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $classes }}">
        <span class="h-2 w-2 rounded-full bg-current opacity-70"></span>
        {{ $text }}
        <span class="text-xs font-medium opacity-70">
            rimanenti
        </span>
    </span>
</div>
