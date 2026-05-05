<?php

namespace App\Services;

use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailTemplateService
{
    /**
     * Firma standard dinamica — legge i contatti da SchoolSetting.
     */
    private function signature(): string
    {
        $legalName = e(SchoolSetting::schoolLegalName());
        $address   = e(SchoolSetting::schoolFullAddress());
        $phone     = e(SchoolSetting::schoolPhone());
        $mobile    = e(SchoolSetting::schoolMobile());
        $website   = e(SchoolSetting::schoolWebsite());

        $phoneTel    = preg_replace('/[^0-9+]/', '', $phone);
        $mobileClean = preg_replace('/[^0-9]/', '', $mobile);

        $phoneRow  = $phone  ? "<a href=\"tel:{$phoneTel}\" style=\"color:#1e3a5f; text-decoration:none;\">{$phone}</a><br>" : '';
        $mobileRow = $mobile ? "<a href=\"https://wa.me/{$mobileClean}\" style=\"color:#1e3a5f; text-decoration:none;\">{$mobile}</a><br>" : '';
        $websiteRow = $website ? "<a href=\"{$website}\" style=\"color:#1e3a5f;\">{$website}</a>" : '';

        return <<<HTML
<hr style="border:none; border-top:1px solid #dde5ef; margin:32px 0 20px;">
<table cellpadding="0" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#555; line-height:1.7;">
  <tr>
    <td>
      <strong style="color:#1e3a5f;">Segreteria</strong><br>
      <strong>{$legalName}</strong><br>
      {$address}<br>
      {$phoneRow}
      {$mobileRow}
      {$websiteRow}
    </td>
  </tr>
</table>
HTML;
    }

    /**
     * Avvolge il contenuto del template in un layout email responsive.
     * Header e footer leggono nome e indirizzo da SchoolSetting.
     */
    private function wrapInLayout(string $bodyContent): string
    {
        $signature  = $this->signature();
        $name       = e(SchoolSetting::schoolName());
        $legalName  = e(SchoolSetting::schoolLegalName());
        $address    = e(SchoolSetting::schoolFullAddress());
        $website    = SchoolSetting::schoolWebsite();
        $privacyUrl = rtrim($website, '/') . '/privacy';

        // Mostra solo il dominio nell'header (senza https://)
        $websiteDisplay = preg_replace('#^https?://(www\.)?#', '', $website);

        return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:8px; overflow:hidden; max-width:600px; width:100%;">
      <!-- Header -->
      <tr>
        <td style="background:#1e3a5f; padding:24px 40px; text-align:center;">
          <h1 style="margin:0; color:#fff; font-size:20px; letter-spacing:1px;">{$name}</h1>
          <p style="margin:4px 0 0; color:#b0c8e8; font-size:13px;">
            <a href="{$website}" style="color:#b0c8e8; text-decoration:none;">{$websiteDisplay}</a>
          </p>
        </td>
      </tr>
      <!-- Body -->
      <tr>
        <td style="padding:32px 40px; font-size:15px; color:#333; line-height:1.7;">
          {$bodyContent}
          {$signature}
        </td>
      </tr>
      <!-- Footer -->
      <tr>
        <td style="background:#f0f4f8; border-top:1px solid #dde5ef; padding:16px 40px; text-align:center;">
          <p style="margin:0; font-size:12px; color:#888;">
            {$legalName} — {$address}<br>
            <a href="{$privacyUrl}" style="color:#888;">Privacy Policy</a>
          </p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    // ─── API pubblica ──────────────────────────────────────────────────────────

    /**
     * Invia un'email usando il template identificato da slug.
     *
     * @param  string  $slug        Slug del template (es. 'welcome_student')
     * @param  string  $toEmail     Destinatario principale
     * @param  string  $toName      Nome destinatario principale
     * @param  array   $variables   Variabili da sostituire (es. ['nome' => 'Mario'])
     * @param  array   $attachments Array di ['data' => binary, 'name' => 'file.pdf', 'mime' => 'application/pdf']
     * @param  array   $cc          Destinatari in copia — array di ['email' => '...', 'name' => '...']
     * @return bool    true se inviata, false altrimenti
     */
    public function sendBySlug(
        string $slug,
        string $toEmail,
        string $toName,
        array  $variables   = [],
        array  $attachments = [],
        array  $cc          = []
    ): bool {
        $template = EmailTemplate::findBySlug($slug);

        if (! $template) {
            Log::warning("EmailTemplateService: template '{$slug}' non trovato o disattivato.");
            return false;
        }

        return $this->sendTemplate($template, $toEmail, $toName, $variables, $attachments, $cc);
    }

    /**
     * Invia un'email usando il template associato a un evento trigger.
     *
     * @param  array  $cc  Destinatari in copia — array di ['email' => '...', 'name' => '...']
     */
    public function sendByEvent(
        string $event,
        string $toEmail,
        string $toName,
        array  $variables   = [],
        array  $attachments = [],
        array  $cc          = []
    ): bool {
        $template = EmailTemplate::findByEvent($event);

        if (! $template) {
            Log::info("EmailTemplateService: nessun template attivo per evento '{$event}'.");
            return false;
        }

        return $this->sendTemplate($template, $toEmail, $toName, $variables, $attachments, $cc);
    }

    /**
     * Invia usando un'istanza EmailTemplate già caricata.
     * Il corpo viene automaticamente avvolto nel layout con header, firma e footer.
     *
     * @param  array  $cc  Destinatari in copia — array di ['email' => '...', 'name' => '...']
     */
    public function sendTemplate(
        EmailTemplate $template,
        string        $toEmail,
        string        $toName,
        array         $variables   = [],
        array         $attachments = [],
        array         $cc          = []
    ): bool {
        [$subject, $rawBody] = array_values($template->render($variables));

        // Avvolge il contenuto nel layout completo con firma
        $fullHtml = $this->wrapInLayout($rawBody);

        try {
            $mailable = new TemplateMail($fullHtml, $subject, $attachments);

            // Costruisce la mailer chain: destinatario principale + eventuali CC
            $mailer = Mail::to($toEmail, $toName);

            if (! empty($cc)) {
                // Normalizza in array di [email, name] compatibile con Laravel
                $ccList = array_map(
                    fn ($entry) => [$entry['email'], $entry['name'] ?? ''],
                    $cc
                );
                $mailer = $mailer->cc($ccList);
            }

            if (config('queue.default', 'sync') !== 'sync') {
                $mailer->queue($mailable);
            } else {
                $mailer->send($mailable);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("EmailTemplateService: errore invio a {$toEmail} [{$template->slug}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Anteprima HTML del template con variabili sostituite e layout completo.
     */
    public function preview(string $slug, array $variables = []): ?string
    {
        $template = EmailTemplate::where('slug', $slug)->first();
        if (! $template) {
            return null;
        }

        $rendered = $template->render($variables);
        return $this->wrapInLayout($rendered['body']);
    }
}
