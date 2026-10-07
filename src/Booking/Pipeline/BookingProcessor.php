<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Pipeline;

use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Psr\Log\LoggerInterface;

/**
 * Runs the pipeline for a booking and handles its failure, by an exception or an error result.
 *
 * Nothing retries the pipeline later on its own, so a failure is resolved right away: a booking that was just
 * submitted is discarded, a booking that is already under way is flagged for the editors.
 */
final readonly class BookingProcessor
{
    public function __construct(
        private BookingPipeline $pipeline,
        private BookingFactory  $bookingFactory,
        private LoggerInterface $logger,
    ) {}

    /**
     * Processes a booking that was just submitted. If processing fails, the booking is discarded: it no longer blocks
     * its period and the visitor can try again.
     *
     * @return bool Whether the booking was processed; false if processing failed and the booking was discarded.
     */
    public function processSubmitted(BookingModel $booking): bool
    {
        if (null === $error = $this->run($booking)) {
            return true;
        }

        $this->logger->error('Booking processing failed, discarding it: ' . $error, ['booking' => $booking->id]);
        $this->discard((int) $booking->id);

        return false;
    }

    /**
     * Continues processing a booking that is already under way, e.g. after its opt-in was confirmed or when an editor
     * processes it again.
     *
     * If processing fails, the booking is kept, since the visitor already confirmed it, and flagged with the reason
     * (processingFailed), so the editors see it in the backend and can process it again.
     *
     * @return bool Whether processing succeeded.
     */
    public function processPending(BookingModel $booking): bool
    {
        if (null === $error = $this->run($booking)) {
            if ($booking->processingFailed) {
                $booking->processingFailed = false;
                $booking->unset('processingError');
                $booking->save();
            }

            return true;
        }

        $this->logger->error('Booking processing failed, flagged it for the editors: ' . $error, ['booking' => $booking->id]);

        try {
            $booking->processingFailed = true;
            $booking->set('processingError', $error);
            $booking->save();
        } catch (\Throwable $e) {
            $this->logger->critical('Could not flag a booking whose processing failed.', ['exception' => $e, 'booking' => $booking->id]);
        }

        return false;
    }

    /**
     * @return string|null Why processing failed, or null if it succeeded.
     */
    private function run(BookingModel $booking): ?string
    {
        try {
            $result = $this->pipeline->process($booking);
        } catch (\Throwable $e) {
            $this->logger->error('Exception while processing a booking.', ['exception' => $e, 'booking' => $booking->id]);

            return $e::class . ': ' . $e->getMessage();
        }

        return $result->isError() ? (string) $result->message() : null;
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
