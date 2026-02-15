<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Pipeline;

use HeimrichHannot\ResourceBookingBundle\Booking\Step\BookingStepInterface;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

readonly class BookingPipeline
{
    private array $steps;

    public function __construct(
        #[TaggedIterator('huh.resource_booking.booking_step', defaultIndexMethod: 'getName')]
        private iterable $stepsIterable,
    ) {}

    /**
     * @return BookingStepInterface[]
     */
    public function getSteps(): array
    {
        if (isset($this->steps)) {
            return $this->steps;
        }

        $steps = \iterator_to_array($this->stepsIterable);

        \uasort($steps, static function (BookingStepInterface $a, BookingStepInterface $b): int {
            $cmp = $b->getPriority() <=> $a->getPriority(); // sort by descending priority
            return $cmp === 0 ? \strcmp($a->getName(), $b->getName()) : $cmp;
        });

        return $this->steps = $steps;
    }

    public function process(BookingModel $booking): StepResult
    {
        $everApplied = false;

        $maxPasses = 20;
        $pass = 0;

        while ($pass++ < $maxPasses)
        {
            $anyApplied = false;

            foreach ($this->getSteps() as $step)
            {
                if (!$step->applies($booking)) {
                    continue;
                }

                $everApplied = true;
                $anyApplied = true;

                $sigBefore = $booking->signature();

                $result = $step->process($booking);

                if ($result->isDone()) {
                    throw new \RuntimeException('Steps MUST NOT return DONE; reserved for pipeline control.');
                }

                if ($result->isRestart())
                {
                    if ($sigBefore === $booking->signature()) {
                        return StepResult::error('Steps MUST NOT request RESTART without state change.');
                    }

                    continue 2;
                }

                if ($result->isNext()) {
                    continue;
                }

                if ($result->isFinish()) {
                    break;
                }

                return $result;
            }

            return StepResult::done([
                'anyApplied' => $anyApplied,
                'everApplied' => $everApplied,
            ]);
        }

        return StepResult::error('Max passes exceeded (possible restart loop).');
    }
}