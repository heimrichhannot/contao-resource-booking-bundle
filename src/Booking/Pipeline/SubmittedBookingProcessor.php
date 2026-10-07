<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Pipeline;

use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Psr\Log\LoggerInterface;

/**
 * Runs the pipeline for a booking that was just submitted.
 *
 * Nothing retries the pipeline later, so a booking whose processing fails, by an exception or an error result, is
 * discarded: it no longer blocks its period and the visitor can try again.
 */
final readonly class SubmittedBookingProcessor
{
    public function __construct(
        private BookingPipeline $pipeline,
        private BookingFactory  $bookingFactory,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return bool Whether the booking was processed; false if processing failed and the booking was discarded.
     */
    public function process(BookingModel $booking): bool
    {
        try {
            $result = $this->pipeline->process($booking);
        } catch (\Throwable $e) {
            $this->logger->error('Could not process booking, discarding it.', ['exception' => $e, 'booking' => $booking->id]);
            $this->discard((int) $booking->id);
            return false;
        }

        if ($result->isError()) {
            $this->logger->error('Booking processing failed, discarding it: ' . $result->message(), ['booking' => $booking->id]);
            $this->discard((int) $booking->id);
            return false;
        }

        return true;
    }

    private function discard(int $bookingId): void
    {
        try {
            $this->bookingFactory->discard($bookingId);
        } catch (\Throwable $e) {
            $this->logger->critical('Could not discard a booking that failed processing, it blocks its period.', ['exception' => $e, 'booking' => $bookingId]);
        }
    }
}
