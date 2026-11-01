<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'module_type',
        'file_name',
        'total_rows',
        'inserted_rows',
        'updated_rows',
        'failed_rows',
        'error_details',
    ];

    protected $casts = [
        'error_details' => 'array',
        'total_rows' => 'integer',
        'inserted_rows' => 'integer',
        'updated_rows' => 'integer',
        'failed_rows' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
