<x-filament-panels::page>
    <div class="space-y-4">
        @if (empty($installments))
            <div class="p-4 bg-white rounded-xl shadow border border-gray-200">
                Nessuna rata trovata.
            </div>
        @else
            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">N.</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Scadenza</th>
                            <th class="px-4 py-3 text-left">Importo</th>
                            <th class="px-4 py-3 text-left">Stato</th>
                            <th class="px-4 py-3 text-left">Pagata il</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($installments as $item)
                            <tr class="border-t">
                                <td class="px-4 py-3">
                                    {{ $item['number'] ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $item['is_deposit'] ? 'Acconto' : 'Rata' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $item['due_date'] ? \Illuminate\Support\Carbon::parse($item['due_date'])->format('d/m/Y') : '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ number_format((float) $item['amount'], 2, ',', '.') }} €
                                </td>

                                <td class="px-4 py-3">
                                    {{ $item['status'] ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $item['paid_at'] ? \Illuminate\Support\Carbon::parse($item['paid_at'])->format('d/m/Y H:i') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>