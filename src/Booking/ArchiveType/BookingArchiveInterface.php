<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\ArchiveType;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('huh.rb.booking_archive_type')]
interface BookingArchiveInterface
{
    public static function getName(): string;

    public static function getTemplate(): string;
}