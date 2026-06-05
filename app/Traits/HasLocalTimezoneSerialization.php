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
        // Formats as Y-m-d H:i:s in local timezone (IST)
        return $date->format('Y-m-d H:i:s');
    }
}
