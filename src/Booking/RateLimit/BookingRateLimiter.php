<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\RateLimit;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Limits booking submissions per client and per email address.
 *
 * Only real attempts count: the client limit applies to submissions that passed form validation, the email limit to
 * bookings that were actually created. So failed attempts cannot lock out a visitor or someone else's email address.
 */
final readonly class BookingRateLimiter
{
    public function __construct(
        #[Autowire(service: 'limiter.huh_rb_booking_client')]
        private RateLimiterFactory $clientLimiter,
        #[Autowire(service: 'limiter.huh_rb_booking_email')]
        private RateLimiterFactory $emailLimiter,
    ) {}

    /**
     * Counts a booking attempt of the client and returns whether it is within the limit.
     */
    public function consumeAttempt(?string $clientIp): bool
    {
        return $this->clientLimiter->create($clientIp ?? 'unknown')->consume()->isAccepted();
    }

    /**
     * Whether another booking may be created for the email address, without counting it.
     */
    public function canBook(string $email): bool
    {
        return $this->emailLimiter->create($this->emailKey($email))->consume(0)->getRemainingTokens() > 0;
    }

    /**
     * Counts a booking that was created for the email address.
     */
    public function recordBooking(string $email): void
    {
        $this->emailLimiter->create($this->emailKey($email))->consume();
    }

    private function emailKey(string $email): string
    {
        return \hash('sha256', \mb_strtolower(\trim(\html_entity_decode($email))));
    }
}
