<?php

namespace App\Filament\Studente\Pages;

use App\Filament\Studente\Concerns\HasStudentScope;
use App\Models\Contract;
use App\Models\Installment;
use Filament\Pages\Page;

class RatePage extends Page
{
    use HasStudentScope;

    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Scadenze e pagamenti';
    protected static ?string $title           = 'Scadenze e pagamenti';
    protected static string  $view            = 'filament.studente.pages.rate-page';
    // La voce di navigazione è gestita da StudentInstallmentResource per evitare duplicati
    protected static bool    $shouldRegisterNavigation = false;

    public array $installments = [];

    public function mount(): void
    {
        $student = $this->getStudent();

        if (! $student) {
            $this->installments = [];
            return;
        }

        $contract = Contract::query()
            ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
            ->where('status', 'active')
            ->latest('id')
            ->first()
            ?? Contract::query()
                ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
                ->latest('id')
                ->first();

        if (! $contract) {
            $this->installments = [];
            return;
        }

        $this->installments = Installment::query()
            ->where('contract_id', $contract->id)
            ->orderBy('due_date')
            ->get()
            ->map(function (Installment $item) {
                return [
                    'id' => $item->id,
                    'number' => $item->number,
                    'is_deposit' => (bool) $item->is_deposit,
                    'due_date' => $item->due_date,
                    'amount' => $item->amount,
                    'status' => $item->status,
                    'paid_at' => $item->paid_at,
                ];
            })
            ->toArray();
    }
}