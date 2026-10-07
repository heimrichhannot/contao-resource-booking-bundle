<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Step;

use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Exception\OptInException;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Util\EmailAddress;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

readonly class OptInStep implements BookingStepInterface
{
    /** How long an unconfirmed booking reserves its period, in seconds (set by BookingFactory) */
    public const RESERVATION_TTL = 3600;

    private const TOKEN_PREFIX = 'huhrb';
    /** Contao opt-in tokens are 24 characters: the prefix, a dash and hexadecimal characters */
    private const TOKEN_PATTERN = '/^huhrb-[0-9a-f]{18}$/';

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

        if (!$booking->get('optInToken'))
        {
            if (!($email = $booking->email) || !EmailAddress::isSingle($email)) {
                return StepResult::error('Invalid email address');
            }

            $token = $this->createOptInToken($booking, $email);
            $this->sendOptInRequestEmail($booking, $email, $token);

            $booking->status = self::getName();
            $booking->set('optInToken', $token->getIdentifier());
            $booking->save();

            return StepResult::wait('Sent opt-in request email.');
        }

        $booking->save();

        return StepResult::wait('Waiting for opt-in confirmation.');
    }

    private function createOptInToken(BookingModel $booking, string $email): OptInTokenInterface
    {
        return $this->optIn->create(self::TOKEN_PREFIX, $email, [
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
     * Confirms the opt-in of a booking.
     *
     * @throws OptInException with a translated message that is safe to show to the visitor
     */
    public function confirmToken(string $tokenId): BookingModel
    {
        if (!\preg_match(self::TOKEN_PATTERN, $tokenId) || !$token = $this->optIn->find($tokenId)) {
            throw $this->optInException('messages.opt_in_invalid');
        }

        if ($token->isConfirmed()) {
            throw $this->optInException('messages.opt_in_already_confirmed');
        }

        if (!$token->isValid()) {
            throw $this->optInException('messages.opt_in_expired');
        }

        $related = $token->getRelatedRecords();
        $bookingIds = $related[BookingModel::getTable()] ?? null;

        if (\count($related) !== 1 || !\is_array($bookingIds) || \count($bookingIds) !== 1) {
            throw $this->optInException('messages.opt_in_invalid');
        }

        $booking = BookingModel::findByPk((int) \reset($bookingIds));

        // The token must be the one currently issued for a booking that is still waiting for its opt-in
        if (!$booking instanceof BookingModel
            || $booking->status !== self::getName()
            || $booking->get('optInToken') !== $token->getIdentifier())
        {
            throw $this->optInException('messages.opt_in_invalid');
        }

        if ($booking->expiresAt && (int) $booking->expiresAt < \time()) {
            throw $this->optInException('messages.opt_in_expired');
        }

        $token->confirm();

        $booking->set('optedInAt', \time());
        $booking->expiresAt = null;
        $booking->unset('optInToken');
        $booking->save();

        return $booking;
    }

    private function optInException(string $messageKey): OptInException
    {
        return new OptInException($this->trans->trans($messageKey, [], 'huh_rb'));
    }
}