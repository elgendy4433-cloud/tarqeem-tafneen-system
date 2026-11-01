<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'iqama_alert_threshold',
        'contract_alert_threshold',
        'alert_emails',
        'is_alert_enabled',
    ];

    protected $casts = [
        'iqama_alert_threshold' => 'integer',
        'contract_alert_threshold' => 'integer',
        'is_alert_enabled' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * إرجاع قائمة الإيميلات كمصفوفة
     */
    public function getRecipientsListAttribute(): array
    {
        if (empty($this->alert_emails)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->alert_emails)));
    }
}
