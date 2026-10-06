<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Payload;

/**
 * Validated contents of the `rb_data` field.
 *
 * Start and end are whole days: both are normalized to midnight in the server's time zone, and the end day is
 * inclusive.
 */
final readonly class BookingPayload
{
    /**
     * @param int[] $resourceIds Unique IDs of the requested resources.
     */
    public function __construct(
        public array              $resourceIds,
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
    ) {}

    /**
     * Number of booked days, including the start and the end day.
     */
    public function getDurationDays(): int
    {
        return $this->start->diff($this->end)->days + 1;
    }
}
