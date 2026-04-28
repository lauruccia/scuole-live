<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                Invia comunicazione
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>