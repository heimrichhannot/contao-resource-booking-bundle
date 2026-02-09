<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\Validator;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class OptInStep implements BookingStepInterface
{
    public static function getName(): string
    {
        return 'pending:opt_in';
    }

    public function __construct(
        private readonly OptIn $optIn,
        private readonly NotificationCenter $nc,
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
        if (!($email = $booking->email) || !Validator::isEmail($email)) {
            return StepResult::error('Invalid email address');
        }

        if ($booking->optedInAt)
        {
            if ($booking->state === self::getName()) {
                $booking->state = null;
            }

            $booking->optInToken = null;
            $booking->save();

            return StepResult::next();
        }

        if ($booking->optInToken && $booking->optInExpiresAt < time())
        {
            $booking->optInToken = null;
            $booking->optInExpiresAt = null;
            $booking->save();
        }

        if (!$booking->optInToken)
        {
            $token = $this->createOptInToken($booking, $email);
            $this->sendOptInRequestEmail($booking, $email, $token);

            $booking->optInToken = $token->getIdentifier();
            $booking->optInExpiresAt = time() + 3600; // 1 hour
            $booking->state = self::getName();
            $booking->save();

            return StepResult::wait('Sent opt-in request email.');
        }

        return StepResult::wait('Waiting for opt-in confirmation.');
    }

    private function createOptInToken(BookingModel $booking, string $email): OptInTokenInterface
    {
        return $this->optIn->create('huh_resource_booking-', $email, [
            $booking::getTable() => [ $booking->id ],
        ]);
    }

    private function sendOptInRequestEmail(BookingModel $booking, string $email, OptInTokenInterface $token): void
    {
        if (!$archive = $booking->getArchive()) {
            return;
        }

        if (!$archive->nc_optInRequest) {
            $this->sendBasicOptInRequestEmail($token);
            return;
        }

        $receipts = $this->nc->sendNotification($archive->nc_optInRequest, []);

        if ($receipts->count() < 1) {
            $this->sendBasicOptInRequestEmail($token);
        }
    }

    private function sendBasicOptInRequestEmail(OptInTokenInterface $token): void
    {
        // todo
        // $token->send('Confirm Opt-In', 'text');
    }
}