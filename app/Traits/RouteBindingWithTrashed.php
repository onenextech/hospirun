<?php

namespace App\Traits;

trait RouteBindingWithTrashed
{
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withTrashed()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
