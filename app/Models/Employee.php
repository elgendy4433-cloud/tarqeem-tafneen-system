<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'national_id',
        'profession',
        'skill_classification',
        'department',
        'branch',
        'phone',
        'email',
        'notes',
    ];

    public function iqama(): HasOne
    {
        return $this->hasOne(Iqama::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function latestContract(): HasOne
    {
        return $this->hasOne(Contract::class)->latestOfMany('contract_end_date');
    }
}
