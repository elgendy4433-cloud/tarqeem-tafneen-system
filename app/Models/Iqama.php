<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Iqama extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'name',
        'iqama_number',
        'profession',
        'branch',
        'issue_date',
        'expiry_date',
        'notes',
        'transfer_renewal_notes',
        'alert_sent_at',
        'alerted_for_expiry',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'alert_sent_at' => 'datetime',
        'alerted_for_expiry' => 'date',
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
     * حساب الأيام المتبقية تلقائياً بالنسبة لتاريخ اليوم
     * قيمة سالبة تعني أن الإقامة منتهية
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expiry_date) {
            return 0;
        }

        $today = Carbon::today();
        return (int) $today->diffInDays($this->expiry_date, false);
    }

    /**
     * احتساب الحالة برمجياً دون الاعتماد على معادلات الإكسل
     */
    public function getStatusAttribute(): string
    {
        $days = $this->days_remaining;

        if ($days < 0) {
            return 'منتهي';
        }

        // الحد الافتراضي 45 يوماً
        $threshold = 45;
        if ($this->relationLoaded('organization') && $this->organization?->setting) {
            $threshold = $this->organization->setting->iqama_alert_threshold;
        }

        if ($days <= $threshold) {
            return 'قريب الانتهاء';
        }

        return 'ساري';
    }

    /**
     * فئات ألوان CSS الخاصة بالواجهة
     */
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

    // Scopes للفلترة السريعة في قاعدة البيانات
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<', Carbon::today());
    }

    public function scopeNearToExpire(Builder $query, int $days = 45): Builder
    {
        $today = Carbon::today();
        return $query->where('expiry_date', '>=', $today)
                     ->where('expiry_date', '<=', $today->copy()->addDays($days));
    }

    public function scopeValid(Builder $query, int $days = 45): Builder
    {
        return $query->where('expiry_date', '>', Carbon::today()->addDays($days));
    }
}
