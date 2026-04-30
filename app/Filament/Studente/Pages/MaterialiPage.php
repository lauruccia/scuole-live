<?php

namespace App\Filament\Studente\Pages;

use App\Filament\Studente\Concerns\HasStudentScope;
use Filament\Pages\Page;

class MaterialiPage extends Page
{
    use HasStudentScope;

    protected static ?string $navigationIcon  = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationLabel = 'Materiali didattici';
    protected static ?string $title           = 'Materiali didattici';
    protected static string  $view            = 'filament.studente.pages.materiali-page';
    protected static ?string $navigationGroup = 'Area Studente';
    protected static ?int    $navigationSort  = 25;

    public array $materials = [];

    public function mount(): void
    {
        $contract = $this->getActiveContract();

        if (! $contract) {
            $this->materials = [];
            return;
        }

        // Carica i materiali assegnati a questo contratto (solo quelli visibili),
        // tramite la tabella pivot contract_course_material
        $this->materials = $contract->materials()
            ->wherePivot('is_visible', true)
            ->orderBy('material_type')
            ->orderBy('course_materials.id', 'desc')
            ->get()
            ->toArray();
    }


}
