<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;

class OptInStep implements BookingStepInterface
{
    public static function getName(): string
    {
        return 'pending:opt_in';
    }

    public function __construct(
        private readonly OptIn $optIn,
    ) {}

    public function getPriority(): int
    {
        return 800;
    }

    public function applies(BookingModel $booking): bool
    {
        if (!$archive = $booking->getArchive()) {
            return false;
        }

        if (!$archive->requireOptIn) {
            return false;
        }

        if ($booking->optedInAt) {
            return false;
        }

        return true;
    }

    public function process(BookingModel $booking): StepResult
    {
        // todo: if no token sent, send one, else wait

        return StepResult::wait();
    }

    public function createOptInToken(string $email, BookingModel $booking): OptInTokenInterface
    {
        return $this->optIn->create('huh_resource_booking-', $email, [
            $booking::getTable() => [ $booking->id ],
        ]);
    }
}