<x-filament-widgets::widget>
    <x-filament::section heading="Report">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Pages\Reports\StudentHoursReport::getUrl(panel: filament()->getCurrentPanel()?->getId()) }}"
                icon="heroicon-o-user-group"
            >
                Report ore studenti
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Pages\Reports\TeacherHoursReport::getUrl(panel: filament()->getCurrentPanel()?->getId()) }}"
                icon="heroicon-o-academic-cap"
            >
                Report ore docenti
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
