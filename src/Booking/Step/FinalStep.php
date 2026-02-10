<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use HeimrichHannot\ResourceBookingBundle\Booking\FinalState;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

readonly class FinalStep implements BookingStepInterface
{
    public static function getName(): string
    {
        return 'finalize';
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function applies(BookingModel $booking): bool
    {
        return !$booking->status
            || $booking->status === self::getName()
            || FinalState::tryFrom($booking->status);
    }

    public function process(BookingModel $booking): StepResult
    {
        if (!FinalState::tryFrom($booking->status))
        {
            $approval = $booking->get('approval');
            $approval = \is_null($approval)
                ? null
                : \filter_var($approval, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);

            $booking->status = match($approval) {
                true => FinalState::APPROVED->value,
                false => FinalState::REJECTED->value,
                null => FinalState::IRRESOLUTE->value,
            };

            $booking->save();
        }

        return StepResult::finish();
    }
}