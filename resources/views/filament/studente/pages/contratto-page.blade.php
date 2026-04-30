<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ── Nessun contratto ──────────────────────────────────────────── --}}
        @if (!$contract)
            <div class="p-6 bg-white rounded-xl shadow border border-gray-200 text-center text-gray-500">
                Nessun contratto trovato.
            </div>

        @else

            {{-- ── Riepilogo dati contratto ──────────────────────────────── --}}
            <div class="p-6 bg-white rounded-xl shadow border border-gray-200">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-5 h-5 text-amber-500"/>
                    Contratto #{{ $contract->id }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700">
                    <div><strong>Corso:</strong> {{ $contract->course?->name ?? '-' }}</div>
                    <div><strong>Data ammissione:</strong> {{ $contract->admission_date?->format('d/m/Y') ?? '-' }}</div>
                    <div><strong>Inizio contratto:</strong> {{ $contract->starts_at?->format('d/m/Y') ?? '-' }}</div>
                    <div><strong>Fine contratto:</strong> {{ $contract->ends_at?->format('d/m/Y') ?? '-' }}</div>
                    <div><strong>Ore acquistate:</strong> {{ $contract->hours_purchased ?? 0 }}</div>
                    <div><strong>Ore consumate:</strong> {{ $contract->hours_consumed ?? 0 }}</div>
                    <div><strong>Ore rimanenti:</strong> {{ $contract->hours_remaining ?? 0 }}</div>
                    <div><strong>Prezzo corso:</strong> {{ number_format((float) $contract->course_price, 2, ',', '.') }} €</div>
                    <div><strong>Quota iscrizione:</strong> {{ number_format((float) $contract->enrollment_fee, 2, ',', '.') }} €</div>
                    <div><strong>Acconto:</strong> {{ number_format((float) $contract->deposit, 2, ',', '.') }} €</div>
                    <div><strong>Totale:</strong> {{ number_format((float) $contract->total, 2, ',', '.') }} €</div>
                    <div><strong>Residuo:</strong> {{ number_format((float) $contract->residual, 2, ',', '.') }} €</div>
                    <div><strong>Modalità pagamento:</strong> {{ $contract->payment_mode ?? '-' }}</div>
                </div>

                @if (!empty($contract->notes))
                    <div class="mt-4 text-sm text-gray-700">
                        <strong>Note:</strong>
                        <div class="mt-1">{{ $contract->notes }}</div>
                    </div>
                @endif
            </div>

            {{-- ── Sezione firma digitale (visibile solo se abilitata) ─────── --}}
            @php
                $firmaAbilitata = \App\Models\SchoolSetting::isDigitalSignatureEnabled();
            @endphp

            @if ($firmaAbilitata)
                <div class="p-6 bg-white rounded-xl shadow border border-gray-200">
                    <h3 class="text-base font-semibold mb-4 flex items-center gap-2">
                        <x-heroicon-o-pencil-square class="w-5 h-5 text-amber-500"/>
                        Firma digitale
                    </h3>

                    {{-- STATO: già firmato --}}
                    @if ($signPhase === 'signed' || $contract->isSigned())
                        <div class="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <x-heroicon-s-check-badge class="w-7 h-7 text-green-500 mt-0.5 shrink-0"/>
                            <div>
                                <p class="font-semibold text-green-800">Contratto firmato digitalmente</p>
                                @if ($contract->signed_at)
                                    <p class="text-sm text-green-700 mt-1">
                                        Firmato il {{ $contract->signed_at->format('d/m/Y \a\l\l\e H:i') }}
                                    </p>
                                @endif
                            </div>
                        </div>

                    {{-- STATO: OTP inviato, attesa inserimento --}}
                    @elseif ($signPhase === 'otp')
                        <div class="space-y-4">
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                                <strong>Codice inviato!</strong> Controlla la tua email e inserisci il codice a 6 cifre.
                                Il codice è valido per <strong>15 minuti</strong>.
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Codice OTP ricevuto via email
                                </label>
                                <input
                                    type="text"
                                    wire:model.live="otpInput"
                                    maxlength="6"
                                    placeholder="000000"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    class="block w-full max-w-xs rounded-lg border border-gray-300 px-4 py-2.5 text-center text-2xl font-mono font-bold tracking-widest shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                />
                                @if ($otpError)
                                    <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                        <x-heroicon-s-exclamation-circle class="w-4 h-4"/>
                                        {{ $otpError }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex gap-3 flex-wrap">
                                <button
                                    wire:click="confirmSignature"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="confirmSignature">
                                        <x-heroicon-s-check class="w-4 h-4 inline"/>
                                        Conferma firma
                                    </span>
                                    <span wire:loading wire:target="confirmSignature">Verifica in corso…</span>
                                </button>

                                <button
                                    wire:click="requestOtp"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                >
                                    <x-heroicon-o-arrow-path class="w-4 h-4"/>
                                    Invia nuovo codice
                                </button>

                                <button
                                    wire:click="cancelOtp"
                                    class="text-sm text-gray-500 hover:text-gray-700 underline px-2 py-2.5"
                                >
                                    Annulla
                                </button>
                            </div>
                        </div>

                    {{-- STATO: idle → invita a firmare --}}
                    @else
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600">
                                Puoi firmare digitalmente questo contratto. Ti invieremo un codice di verifica
                                a 6 cifre all'email indicata nel contratto per completare la firma.
                            </p>

                            <div class="flex items-center gap-3 text-sm text-gray-500 bg-gray-50 rounded-lg px-4 py-3 border border-gray-200">
                                <x-heroicon-o-envelope class="w-5 h-5 text-gray-400 shrink-0"/>
                                Il codice sarà inviato a:
                                <strong class="text-gray-800">{{ $contract->signature_email ?? '—' }}</strong>
                            </div>

                            <button
                                wire:click="requestOtp"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="requestOtp">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 inline"/>
                                    Firma il contratto
                                </span>
                                <span wire:loading wire:target="requestOtp">Invio codice…</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

        @endif

    </div>
</x-filament-panels::page>
