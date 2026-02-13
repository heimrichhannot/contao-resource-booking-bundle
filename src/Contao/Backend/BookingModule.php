<?php

namespace HeimrichHannot\ResourceBookingBundle\Contao\Backend;

use HeimrichHannot\ResourceBookingBundle\Contao\Table;

readonly class BookingModule
{
    public const CATEGORY = 'huh_rb';
    public const NAME = 'rb_booking';

    public static function getTables(): array
    {
        return [
            Table::BOOKING_ARCHIVE->value,
            Table::BOOKING->value,
            Table::BOOKING_RESOURCE->value,
        ];
    }
}