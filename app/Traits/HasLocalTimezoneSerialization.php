<?php

namespace App\Traits;

use DateTimeInterface;

trait HasLocalTimezoneSerialization
{
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        // Formats as ISO 8601 with the local timezone offset (e.g. +05:30)
        return $date->format('Y-m-d\TH:i:s.uP');
    }
}
