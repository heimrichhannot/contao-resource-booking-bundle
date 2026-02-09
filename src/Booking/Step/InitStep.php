<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

class InitStep implements BookingStepInterface
{

    public static function getName(): string
    {
        return 'init';
    }

    public function getPriority(): int
    {
        return 1000;
    }

    public function applies(BookingModel $booking): bool
    {
        return !$booking->status || $booking->status === self::getName();
    }

    public function process(BookingModel $booking): StepResult
    {
        if (!$archive = $booking->getArchive()) {
            return StepResult::error('Archive not found');
        }

        return StepResult::next();
    }
}