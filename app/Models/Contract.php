<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'name',
        'national_id',
        'profession',
        'skill_classification',
        'department',
        'branch',
        'contractual_relation_start',
        'contract_start_date',
        'contract_end_date',
        'renewals_count',
        'notes',
        'alert_sent_at',
        'alerted_for_expiry',
    ];

    protected $casts = [
        'contractual_relation_start' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'alert_sent_at' => 'datetime',
        'alerted_for_expiry' => 'date',
        'renewals_count' => 'integer',
    ];

    protected $appends = [
        'days_remaining',
        'status',
        'status_badge',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * حساب الأيام المتبقية لاتخاذ إجراء بدقة متناهية
     * ينهي هذا الحساب المباشر مشكلة #VALUE! الناتجة عن صيغ الإكسل
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->contract_end_date) {
            return 0;
        }

        $today = Carbon::today();
        return (int) $today->diffInDays($this->contract_end_date, false);
    }

    /**
     * احتساب الحالة ديناميكياً
     * ساري > 90 يوماً
     * قريب الانتهاء بين 0 و 90 يوماً
     * منتهي < 0
     */
    public function getStatusAttribute(): string
    {
        $days = $this->days_remaining;

        if ($days < 0) {
            return 'منتهي';
        }

        $threshold = 90;
        if ($this->relationLoaded('organization') && $this->organization?->setting) {
            $threshold = $this->organization->setting->contract_alert_threshold;
        }

        if ($days <= $threshold) {
            return 'قريب الانتهاء';
        }

        return 'ساري';
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'منتهي' => [
                'label' => 'منتهي',
                'class' => 'bg-red-100 text-red-800 border-red-300 dark:bg-red-900/40 dark:text-red-300',
                'dot' => 'bg-red-500',
            ],
            'قريب الانتهاء' => [
                'label' => 'قريب الانتهاء',
                'class' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:text-amber-300',
                'dot' => 'bg-amber-500',
            ],
            default => [
                'label' => 'ساري',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-300',
                'dot' => 'bg-emerald-500',
            ],
        };
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('contract_end_date', '<', Carbon::today());
    }

    public function scopeNearToExpire(Builder $query, int $days = 90): Builder
    {
        $today = Carbon::today();
        return $query->where('contract_end_date', '>=', $today)
                     ->where('contract_end_date', '<=', $today->copy()->addDays($days));
    }

    public function scopeValid(Builder $query, int $days = 90): Builder
    {
        return $query->where('contract_end_date', '>', Carbon::today()->addDays($days));
    }
}
