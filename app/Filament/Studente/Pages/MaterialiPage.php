<?php

namespace App\Filament\Studente\Pages;

use App\Filament\Studente\Concerns\HasStudentScope;
use App\Models\Contract;
use Filament\Pages\Page;

class MaterialiPage extends Page
{
    use HasStudentScope;

    protected static ?string $navigationIcon  = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationLabel = 'Materiali didattici';
    protected static ?string $title           = 'Materiali didattici';
    protected static string  $view            = 'filament.studente.pages.materiali-page';
    protected static ?int    $navigationSort  = 3;

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
            ->orderBy('course_materials.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Recupera il contratto attivo dello studente loggato.
     */
    private function getActiveContract(): ?Contract
    {
        $student = $this->getStudent();
        if (! $student) return null;

        return Contract::query()
            ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
            ->where('status', 'active')
            ->latest('id')
            ->first()
            ?? Contract::query()
                ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
                ->latest('id')
                ->first();
    }
}
