<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\Validator;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

readonly class OptInStep implements BookingStepInterface
{
    public static function getName(): string
    {
        return 'pending:opt_in';
    }

    public function __construct(
        private OptIn               $optIn,
        private NotificationCenter  $nc,
        private TranslatorInterface $trans,
    ) {}

    public function getPriority(): int
    {
        return 800;
    }

    public function applies(BookingModel $booking): bool
    {
        if ($booking->status === self::getName()) {
            return true;
        }

        if (!$booking->getArchive()?->requireOptIn) {
            return false;
        }

        return true;
    }

    public function process(BookingModel $booking): StepResult
    {
        if ($booking->get('optedInAt')) {
            return $this->toNext($booking);
        }

        $booking->status = self::getName();

        if ($booking->get('optInToken') && $booking->get('optInExpiresAt') < time())
        {
            $booking->set('optInExpiresAt', null);
            $booking->set('optInToken', null);
        }

        if (!$booking->get('optInToken'))
        {
            if (!($email = $booking->email) || !Validator::isEmail($email)) {
                return StepResult::error('Invalid email address');
            }

            $token = $this->createOptInToken($booking, $email);
            $this->sendOptInRequestEmail($booking, $email, $token);

            $expiresAt = \time() + 3600; // 1 hour

            $booking->status = self::getName();
            $booking->set('optInToken', $token->getIdentifier());
            $booking->set('optInExpiresAt', $expiresAt);
            $booking->set('reservationExpiresAt', $expiresAt);
            $booking->save();

            return StepResult::wait('Sent opt-in request email.');
        }

        $booking->save();

        return StepResult::wait('Waiting for opt-in confirmation.');
    }

    private function createOptInToken(BookingModel $booking, string $email): OptInTokenInterface
    {
        return $this->optIn->create('huhrb-', $email, [
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

        $ncTokens = $booking->collectTokens();
        $ncTokens['token'] = $token->getIdentifier();

        $receipts = $this->nc->sendNotification($archive->nc_optInRequest, $ncTokens);

        if ($receipts->count() < 1) {
            $this->sendBasicOptInRequestEmail($token);
        }
    }

    private function sendBasicOptInRequestEmail(OptInTokenInterface $token): void
    {
        // todo
        // $token->send('Confirm Opt-In', 'text');
    }

    private function toNext(BookingModel $booking): StepResult
    {
        if ($booking->status === self::getName()) {
            $booking->status = null;
        }

        $booking->set('optInToken', null);
        $booking->save();

        return StepResult::next();
    }

    /**
     * @param string $tokenId
     * @return BookingModel
     * @throws \InvalidArgumentException if the token ID is invalid or the token is not related to a booking
     * @throws \RuntimeException if the token is already confirmed or the related booking is not found
     */
    public function confirmToken(string $tokenId): BookingModel
    {
        if (!$token = $this->optIn->find($tokenId)) {
            throw new \InvalidArgumentException($this->trans->trans('messages.opt_in_invalid', [], 'huh_rb'));
        }

        if ($token->isConfirmed()) {
            throw new \RuntimeException($this->trans->trans('messages.opt_in_already_confirmed', [], 'huh_rb'));
        }

        $related = $token->getRelatedRecords();

        if (!\count($related) || \key($related) !== BookingModel::getTable()) {
            throw new \InvalidArgumentException($this->trans->trans('messages.opt_in_invalid', [], 'huh_rb'));
        }

        if (!$booking = BookingModel::findById(\current($related))) {
            throw new \RuntimeException($this->trans->trans('messages.opt_in_invalid', [], 'huh_rb'));
        }

        $token->confirm();

        $booking->set('expiresAt', null);
        $booking->set('optedInAt', \time());
        $booking->set('optInExpiresAt', null);
        $booking->set('optInToken', null);
        $booking->save();

        return $booking;
    }
}