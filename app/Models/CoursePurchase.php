<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CoursePurchase extends Model
{
    use LogsActivity, SoftDeletes;

    // ─── Activity Log (transazioni finanziarie) ──────────────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payments')
            ->logOnly([
                'course_id', 'user_id', 'contract_id',
                'payment_method', 'payment_status', 'amount',
                'stripe_session_id', 'stripe_payment_intent',
                'paypal_order_id', 'bank_transfer_ref',
                'billing_type',
                'billing_first_name', 'billing_last_name', 'billing_email',
                'company_name', 'vat_number', 'billing_tax_code',
                'paid_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName): string {
                $buyer = $this->buyer_name ?: ($this->billing_email ?? "#{$this->id}");
                $amount = number_format((float) $this->amount, 2, ',', '.');
                return match ($eventName) {
                    'created' => "Acquisto corso #{$this->id} creato — {$buyer} ({$amount} €)",
                    'updated' => "Acquisto corso #{$this->id} aggiornato — {$buyer} (stato: {$this->payment_status})",
                    'deleted' => "Acquisto corso #{$this->id} eliminato — {$buyer}",
                    default   => "Acquisto corso #{$this->id} — {$eventName}",
                };
            });
    }


    protected $fillable = [
        'course_id',
        'user_id',
        'contract_id',
        'payment_method',
        'payment_status',
        'amount',
        'stripe_session_id',
        'stripe_payment_intent',
        'paypal_order_id',
        'bank_transfer_ref',
        'billing_type',
        'billing_first_name',
        'billing_last_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_zip',
        'billing_country',
        'billing_tax_code',
        'company_name',
        'vat_number',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'paid_at'  => 'datetime',
    ];

    // ── Relazioni ──────────────────────────────────────────────────────────────

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /** Riferimento univoco bonifico: BNF-YYYYMMDD-ID */
    public static function generateBankRef(int $id): string
    {
        return 'BNF-' . now()->format('Ymd') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
    }

    /** Etichetta metodo pagamento */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'stripe'   => 'Carta di credito',
            'paypal'   => 'PayPal',
            'bonifico' => 'Bonifico bancario',
            default    => $this->payment_method,
        };
    }

    /** Etichetta stato */
    public function getStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'pending'   => 'In attesa',
            'paid'      => 'Pagato',
            'failed'    => 'Fallito',
            'refunded'  => 'Rimborsato',
            'cancelled' => 'Annullato',
            default     => $this->payment_status,
        };
    }

    /** Nome completo acquirente */
    public function getBuyerNameAttribute(): string
    {
        if ($this->billing_type === 'company') {
            return $this->company_name ?? '—';
        }
        return trim(($this->billing_last_name ?? '') . ' ' . ($this->billing_first_name ?? '')) ?: $this->billing_email;
    }
}
