<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait AutoSlug
{
    public function getSluggableColumn()
    {
        return $this->sluggableColumn ?? "name";
    }

    public static function bootAutoSlug()
    {
        static::creating(function ($model) {
           $model->slug = Str::slug($model->{$model->getSluggableColumn()});
        });
        static::updating(function ($model) {
           $model->slug = Str::slug($model->{$model->getSluggableColumn()});
        });
    }
}
