<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'tax_number',
        'commercial_registration',
        'is_active',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function iqamas(): HasMany
    {
        return $this->hasMany(Iqama::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(SystemSetting::class);
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }
}
