<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('huh.resource_booking.booking_step')]
interface BookingStepInterface
{
    public static function getName(): string;

    /**
     * - *1000*: initialization/normalization
     * -  *800*: opt-in
     * -  *600*: payment
     * -  *400*: admin-approval
     * -  *200*: finalize (allocate resource, send confirmation)
     * -    *0*: fallback/default
     *
     * @return int
     */
    public function getPriority(): int;

    public function applies(BookingModel $booking): bool;

    public function process(BookingModel $booking): StepResult;
}