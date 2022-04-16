<?php

namespace App\Traits;

use Carbon\Carbon;

trait FormattedDate
{
    public function formatDate($column, $format = 'Y-m-d h:i:s A'): string
    {
        $time = $this->{$column} instanceof Carbon ? $this->{$column} : Carbon::parse($this->{$column});
        return $time->format($format ?: config('app.default_time_format', 'Y-m-d h:i:s A'));
    }
}
