<?php

namespace App\Http\Controllers;

use App\Models\CourseMaterial;
use App\Models\Contract;
use Illuminate\Http\Request;

class MaterialVisibilityController extends Controller
{
    public function __invoke(Request $request, CourseMaterial $material, Contract $contract)
    {
        $pivot = $material->contracts()->where('contract_id', $contract->id)->first();

        if (! $pivot) {
            return back()->with('error', 'Assegnazione non trovata.');
        }

        $current = (bool) $pivot->pivot->is_visible;

        $material->contracts()->updateExistingPivot($contract->id, [
            'is_visible' => ! $current,
        ]);

        return back()->with('success', 'Visibilità aggiornata.');
    }
}
