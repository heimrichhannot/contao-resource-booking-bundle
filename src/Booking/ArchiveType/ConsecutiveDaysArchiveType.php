<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\ArchiveType;

class ConsecutiveDaysArchiveType implements BookingArchiveInterface
{
    public const NAME = 'consecutive_days';

    public static function getName(): string
    {
        return self::NAME;
    }
}