<?php

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            $currentOrgId = session('current_organization_id');
            if ($currentOrgId) {
                $builder->where($builder->getModel()->getTable() . '.organization_id', $currentOrgId);
            }
        });

        static::creating(function (Model $model) {
            if (!$model->organization_id) {
                $model->organization_id = session('current_organization_id') ?? 1;
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
