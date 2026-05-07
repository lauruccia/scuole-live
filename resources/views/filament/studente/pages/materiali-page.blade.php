<x-filament-panels::page>

    @php $materials = collect($materials); @endphp

    @if($materials->isEmpty())
        <x-filament::section>
            <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                <div class="mx-auto mb-3 opacity-40 text-gray-400" style="width:3rem;height:3rem;">
                    <x-heroicon-o-document-arrow-down class="w-full h-full" />
                </div>
                <p class="font-medium">Nessun materiale disponibile</p>
                <p class="text-sm mt-1">I tuoi materiali didattici appariranno qui quando il docente o la segreteria li caricheranno.</p>
            </div>
        </x-filament::section>
    @else
        @php
            $byType = $materials->groupBy(fn($m) => is_array($m) ? $m['material_type'] : $m->material_type);
            $typeLabels = \App\Models\CourseMaterial::MATERIAL_TYPES;
        @endphp

        @foreach($byType as $type => $items)
            <x-filament::section :heading="$typeLabels[$type] ?? ucfirst($type)">
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($items as $mat)
                        @php
                            $title       = is_array($mat) ? $mat['title']           : $mat->title;
                            $description = is_array($mat) ? ($mat['description'] ?? '') : ($mat->description ?? '');
                            $matType     = is_array($mat) ? $mat['material_type']   : $mat->material_type;
                            $language    = is_array($mat) ? ($mat['language'] ?? null) : $mat->language;
                            $filePath    = is_array($mat) ? ($mat['file_path'] ?? null) : $mat->file_path;
                            $fileSize    = is_array($mat) ? ($mat['file_size'] ?? 0)  : $mat->file_size;
                            $extUrl      = is_array($mat) ? ($mat['external_url'] ?? null) : $mat->external_url;

                            $isLink = ! empty($extUrl);
                            $url    = $isLink
                                ? $extUrl
                                : ($filePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($filePath) : '#');

                            $icon = match($matType) {
                                'video'    => '▶️',
                                'exercise' => '✏️',
                                'handout'  => '📄',
                                'image'    => '🖼️',
                                default    => '📎',
                            };

                            $bytes     = (int) $fileSize;
                            $sizeHuman = $bytes < 1024 ? "{$bytes} B"
                                : ($bytes < 1048576 ? round($bytes/1024, 1).' KB' : round($bytes/1048576, 1).' MB');
                        @endphp
                        <div class="flex items-center justify-between py-3 gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-2xl flex-shrink-0">{{ $icon }}</span>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate">
                                        {{ $title }}
                                    </p>
                                    @if($description)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ $description }}
                                        </p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        @if($isLink)
                                            🔗 Link esterno
                                        @else
                                            {{ $sizeHuman }}
                                        @endif
                                        @if($language)· {{ $language }}@endif
                                    </p>
                                </div>
                            </div>
                            <a href="{{ $url }}" target="_blank"
                               class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 transition-colors">
                                @if($isLink)
                                    <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4"/>
                                    Apri
                                @else
                                    <x-heroicon-m-arrow-down-tray class="h-4 w-4"/>
                                    Scarica
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    @endif

</x-filament-panels::page>
