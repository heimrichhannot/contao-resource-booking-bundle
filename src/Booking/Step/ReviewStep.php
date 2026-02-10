<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Terminal42\NotificationCenterBundle\NotificationCenter;

readonly class ReviewStep implements BookingStepInterface
{
    public static function getName(): string
    {
        return 'pending:review';
    }

    public function __construct(
        private NotificationCenter $nc,
    ) {}

    public function getPriority(): int
    {
        return 400;
    }

    public function applies(BookingModel $booking): bool
    {
        if ($booking->status === self::getName()) {
            return true;
        }

        if (!$booking->getArchive()?->requireReview) {
            return false;
        }

        return true;
    }

    public function process(BookingModel $booking): StepResult
    {
        if ($booking->get('reviewedAt')) {
            return $this->toNext($booking);
        }

        return StepResult::wait('Waiting for review');
    }

    private function toNext(BookingModel $booking): StepResult
    {
        if ($booking->status === self::getName()) {
            $booking->status = null;
            $booking->save();
        }

        return StepResult::next();
    }

    public function review(BookingModel $booking, bool $approve): void
    {
        $booking->set('reviewedAt', \time());
        $booking->set('approval', $approve);
        $booking->save();

        $ncId = $approve
            ? $booking->getArchive()?->nc_reviewApproval
            : $booking->getArchive()?->nc_reviewRejection;

        if (!$ncId) {
            return;
        }

        $receipts = $this->nc->sendNotification($ncId, $booking->collectTokens());
    }
}