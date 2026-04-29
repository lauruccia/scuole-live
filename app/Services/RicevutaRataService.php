<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\SchoolSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class RicevutaRataService
{
    /**
     * Genera la ricevuta PDF per una rata pagata.
     *
     * @return string PDF binario (pronto per download o allegato email)
     */
    public function generate(Installment $installment): string
    {
        $installment->loadMissing(['contract.students', 'contract.course']);

        $contract = $installment->contract;

        // ── Dati intestatario ─────────────────────────────────────────────────
        $billingName    = $contract->billing_display_name ?? '—';
        $billingEmail   = $contract->billing_email ?? '';
        $billingPhone   = $contract->billing_phone ?? '';
        $billingAddress = implode(', ', array_filter([
            $contract->billing_address ?? '',
            trim(($contract->billing_zip ?? '') . ' ' . ($contract->billing_city ?? '')),
            $contract->billing_country ?? '',
        ]));

        // ── Corso ─────────────────────────────────────────────────────────────
        $courseName = $contract->course?->name ?? null;

        // ── Dati scuola da SchoolSetting ──────────────────────────────────────
        $data = [
            'installment'     => $installment,
            'contract'        => $contract,
            'billingName'     => $billingName,
            'billingEmail'    => $billingEmail,
            'billingPhone'    => $billingPhone,
            'billingAddress'  => $billingAddress,
            'courseName'      => $courseName,

            'schoolName'       => SchoolSetting::schoolName(),
            'schoolLegalName'  => SchoolSetting::schoolLegalName(),
            'schoolAddress'    => SchoolSetting::schoolFullAddress(),
            'schoolFullAddress'=> SchoolSetting::schoolFullAddress(),
            'schoolPhone'      => SchoolSetting::schoolPhone(),
            'schoolEmail'      => SchoolSetting::schoolEmail(),
            'schoolWebsite'    => SchoolSetting::schoolWebsite(),

            'bankIban'         => SchoolSetting::bankIban(),
            'bankIntestatario' => SchoolSetting::bankIntestatario(),
        ];

        $pdf = Pdf::loadView('reports.ricevuta-rata-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        return $pdf->output();
    }

    /**
     * Nome file suggerito per il download.
     */
    public function filename(Installment $installment): string
    {
        $num = str_pad((string) $installment->id, 5, '0', STR_PAD_LEFT);
        return "Ricevuta-RIC{$num}-Rata{$installment->number}.pdf";
    }
}
