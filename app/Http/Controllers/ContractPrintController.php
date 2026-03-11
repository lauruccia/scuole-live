<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ContractPrintController extends Controller
{
    private function ensureCanAccess(): void
    {
        /** @var \App\Models\User|null $u */
        $u = Auth::user();

        abort_unless(
            $u && $u->hasAnyRole(['superadmin', 'amministrazione', 'Segreteria', 'Amministrazione', 'Segreteria']),
            403
        );
    }

    public function print(Contract $contract)
    {
        $this->ensureCanAccess();

        // ✅ carico anche beneficiaries (ContractStudent) e course
        $contract->loadMissing(['course', 'beneficiaries']);

        return response()
            ->view('contracts.print', [
                'contract' => $contract,
                'mode' => 'html',
            ])
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * PDF in streaming (preview)
     */
    public function pdf(Contract $contract)
    {
        $this->ensureCanAccess();

        $contract->loadMissing(['course', 'beneficiaries']);

        $filename = 'contratto-' . $contract->id . '.pdf';

        return Pdf::loadView('contracts.print', [
                'contract' => $contract,
                'mode' => 'pdf',
            ])
            ->setPaper('A4', 'portrait')
            ->stream($filename);
    }

    /**
     * ✅ Download PDF (scarica)
     */
    public function download(Contract $contract)
    {
        $this->ensureCanAccess();

        $contract->loadMissing(['course', 'beneficiaries']);

        $filename = 'contratto-' . $contract->id . '.pdf';

        return Pdf::loadView('contracts.print', [
                'contract' => $contract,
                'mode' => 'pdf',
            ])
            ->setPaper('A4', 'portrait')
            ->download($filename);
    }
}
