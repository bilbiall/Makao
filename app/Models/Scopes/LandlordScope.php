<?php

namespace App\Models\Scopes;

use App\Support\CurrentLandlord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class LandlordScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // A non-superadmin, authenticated user with no landlord_id is a mis-provisioned
        // account - fail closed (show nothing) rather than accidentally leaking every
        // landlord's rows via Eloquent's where(column, null) -> whereNull() behavior.
        if (CurrentLandlord::shouldFailClosed()) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $landlordId = CurrentLandlord::id();

        if ($landlordId !== null) {
            $builder->where($model->getTable() . '.landlord_id', $landlordId);
        }
    }
}
