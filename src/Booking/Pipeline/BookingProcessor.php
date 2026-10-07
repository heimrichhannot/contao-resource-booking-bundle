<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Pipeline;

use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
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
        $result = $this->run($booking);

        if (!$result->isError()) {
            return true;
        }

        $this->logger->error('Booking processing failed, discarding it: ' . $result->message(), ['booking' => $booking->id]);
        $this->discard((int) $booking->id);

        return false;
    }

    /**
     * Continues processing a booking that is already under way, e.g. after its opt-in was confirmed or when an editor
     * processes it again.
     *
     * If processing fails, the booking is kept, since the visitor already confirmed it, and flagged with the reason
     * (processingFailed), so the editors see it in the backend and can process it again. Any other result, e.g. a
     * cancelled booking whose reservation expired, clears the flag.
     *
     * @return StepResult The result of the pipeline, an error if processing failed.
     */
    public function processPending(BookingModel $booking): StepResult
    {
        $result = $this->run($booking);

        if (!$result->isError()) {
            if ($booking->processingFailed) {
                $booking->processingFailed = false;
                $booking->unset('processingError');
                $booking->save();
            }

            return $result;
        }

        $error = (string) $result->message();
        $this->logger->error('Booking processing failed, flagged it for the editors: ' . $error, ['booking' => $booking->id]);

        try {
            $booking->processingFailed = true;
            $booking->set('processingError', $error);
            $booking->save();
        } catch (\Throwable $e) {
            $this->logger->critical('Could not flag a booking whose processing failed.', ['exception' => $e, 'booking' => $booking->id]);
        }

        return $result;
    }

    /**
     * @return StepResult The result of the pipeline, or an error with the exception if it threw one.
     */
    private function run(BookingModel $booking): StepResult
    {
        try {
            return $this->pipeline->process($booking);
        } catch (\Throwable $e) {
            $this->logger->error('Exception while processing a booking.', ['exception' => $e, 'booking' => $booking->id]);

            return StepResult::error($e::class . ': ' . $e->getMessage());
        }
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
