<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\SuperadminPanelProvider::class,

        // ✅ Filament panels

    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\TeacherPanelProvider::class,
        App\Providers\Filament\StudentePanelProvider::class,
];
