<?php

namespace App\Filament\Studente\Pages;

use App\Models\Contract;
use App\Models\Installment;
use Filament\Pages\Page;

class RatePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Rate e scadenze';
    protected static ?string $title = 'Rate e scadenze';
    protected static string $view = 'filament.studente.pages.rate-page';
    protected static bool $shouldRegisterNavigation = false;

    public array $installments = [];

    public function mount(): void
    {
        $student = auth()->user()?->student;

        if (! $student) {
            $this->installments = [];
            return;
        }

        $contract = Contract::query()
            ->whereHas('students', function ($query) use ($student) {
                $query->where('students.id', $student->id);
            })
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