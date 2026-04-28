<x-filament-panels::page>
    <div class="space-y-4">
        @if (!$contract)
            <div class="p-4 bg-white rounded-xl shadow border border-gray-200">
                Nessun contratto trovato.
            </div>
        @else
            <div class="p-4 bg-white rounded-xl shadow border border-gray-200">
                <h3 class="text-lg font-semibold mb-4">
                    Contratto #{{ $contract->id }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700">
                    <div>
                        <strong>Corso:</strong>
                        {{ $contract->course?->name ?? '-' }}
                    </div>

                    <div>
                        <strong>Data ammissione:</strong>
                        {{ $contract->admission_date?->format('d/m/Y') ?? '-' }}
                    </div>

                    <div>
                        <strong>Inizio contratto:</strong>
                        {{ $contract->starts_at?->format('d/m/Y') ?? '-' }}
                    </div>

                    <div>
                        <strong>Fine contratto:</strong>
                        {{ $contract->ends_at?->format('d/m/Y') ?? '-' }}
                    </div>

                    <div>
                        <strong>Ore acquistate:</strong>
                        {{ $contract->hours_purchased ?? 0 }}
                    </div>

                    <div>
                        <strong>Ore consumate:</strong>
                        {{ $contract->hours_consumed ?? 0 }}
                    </div>

                    <div>
                        <strong>Ore rimanenti:</strong>
                        {{ $contract->hours_remaining ?? 0 }}
                    </div>

                    <div>
                        <strong>Prezzo corso:</strong>
                        {{ number_format((float) $contract->course_price, 2, ',', '.') }} €
                    </div>

                    <div>
                        <strong>Quota iscrizione:</strong>
                        {{ number_format((float) $contract->enrollment_fee, 2, ',', '.') }} €
                    </div>

                    <div>
                        <strong>Acconto:</strong>
                        {{ number_format((float) $contract->deposit, 2, ',', '.') }} €
                    </div>

                    <div>
                        <strong>Totale:</strong>
                        {{ number_format((float) $contract->total, 2, ',', '.') }} €
                    </div>

                    <div>
                        <strong>Residuo:</strong>
                        {{ number_format((float) $contract->residual, 2, ',', '.') }} €
                    </div>

                    <div>
                        <strong>Modalità pagamento:</strong>
                        {{ $contract->payment_mode ?? '-' }}
                    </div>
                </div>

                @if (!empty($contract->notes))
                    <div class="mt-4 text-sm text-gray-700">
                        <strong>Note:</strong>
                        <div class="mt-1">{{ $contract->notes }}</div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>