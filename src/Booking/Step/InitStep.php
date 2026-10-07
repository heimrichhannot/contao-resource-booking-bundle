<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use HeimrichHannot\ResourceBookingBundle\Booking\FinalState;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

readonly class InitStep implements BookingStepInterface
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
        return true;
    }

    public function process(BookingModel $booking): StepResult
    {
        if (FinalState::tryFrom($booking->status)?->isResolved()) {
            return StepResult::finish();
        }

        if (!$booking->getArchive()) {
            return StepResult::error('Archive not found');
        }

        // Not a failure: the visitor did not confirm in time, and the booking no longer blocks its period
        if ($booking->expiresAt && $booking->expiresAt < \time()) {
            return StepResult::cancel('Reservation expired at ' . \date('Y-m-d H:i:s', (int) $booking->expiresAt));
        }

        if ($booking->status === self::getName()) {
            $booking->status = null;
            $booking->save();
        }

        return StepResult::next();
    }
}