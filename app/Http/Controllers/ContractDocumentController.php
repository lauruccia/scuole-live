<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractDocumentController extends Controller
{
    public function show(Contract $contract)
    {
        $contract->loadMissing(['course', 'beneficiaries.student']);

        return response()
            ->view('contracts.print', [
                'contract' => $contract,
                'mode' => 'html',
            ])
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function pdf(Contract $contract)
    {
        $contract->loadMissing(['course', 'beneficiaries.student']);

        $filename = 'Contratto_' . $contract->id . '.pdf';

        return Pdf::loadView('contracts.print', [
                'contract' => $contract,
                'mode' => 'pdf',
            ])
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    public function download(Contract $contract)
    {
        $contract->loadMissing(['course', 'beneficiaries.student']);

        $filename = 'Contratto_' . $contract->id . '.pdf';

        return Pdf::loadView('contracts.print', [
                'contract' => $contract,
                'mode' => 'pdf',
            ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
