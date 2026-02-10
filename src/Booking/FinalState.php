<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking;

enum FinalState: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case IRRESOLUTE = 'irresolute';

    public function isResolved(): bool
    {
        return match ($this) {
            self::APPROVED, self::REJECTED => true,
            default => false,
        };
    }
}